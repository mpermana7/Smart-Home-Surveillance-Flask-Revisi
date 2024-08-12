from flask import Flask, render_template, Response, request, session, redirect, url_for, jsonify
import cv2
import time
import mysql.connector
import numpy as np
import os
from ultralytics import YOLO
from threading import Thread
import shutil
# import RPi.GPIO as GPIO

app = Flask(__name__)
app.secret_key = 'sas-2022-2024'

# Load the custom YOLOv8 models
fire_model = YOLO('fire/train4/weights/best.pt')
drowsiness_model = YOLO('kantuk/weights/best.pt')

# Database connection configuration
db_config = {
    'user': 'root',
    'password': '',
    'host': 'localhost',
    'database': 'smarthomesurveillance'
}

# Global variables to store recording states and video writers
recording = {}
video_writers = {}
start_time = {}
output_directory = 'video'
backup_directory = 'C:/xampp_personal/htdocs/SHS_web/video'

if not os.path.exists(output_directory):
    os.makedirs(output_directory)

if not os.path.exists(backup_directory):
    os.makedirs(backup_directory)

# Function to detect active cameras
def detect_active_cameras(max_cameras=4):
    active_cameras = []
    for i in range(max_cameras):
        cap = cv2.VideoCapture(i)
        if cap.isOpened():
            active_cameras.append((i, f'Camera {i}'))
            cap.release()

    # Add RTSP cameras from the database
    active_cameras.extend(get_rtsp_cameras())
    
    return active_cameras

# Function to retrieve RTSP camera URLs from the database
def get_rtsp_cameras():
    rtsp_cameras = []
    connection = mysql.connector.connect(**db_config)
    cursor = connection.cursor()
    cursor.execute("SELECT id_rtsp, kameraRTSP FROM kamera_rtsp")
    for (id_rtsp, kameraRTSP) in cursor:
        rtsp_cameras.append((f'rtsp_{id_rtsp}', kameraRTSP))
    cursor.close()
    connection.close()
    return rtsp_cameras

def insert_data(kameraRTSP):
    conn = mysql.connector.connect(**db_config)
    cursor = conn.cursor()
    sql = "INSERT INTO kamera_rtsp (kameraRTSP) VALUES (%s)"
    val = (kameraRTSP,)
    cursor.execute(sql, val)
    conn.commit()
    cursor.close()

def generate_frames(camera_index):
    global recording, video_writers

    # Check if the camera index is a number or RTSP link
    if isinstance(camera_index, int):
        cap = cv2.VideoCapture(camera_index)
    else:
        cap = cv2.VideoCapture(camera_index)

    if not cap.isOpened():
        # Jika kamera tidak dapat dibuka, tampilkan pesan
        while True:
            frame = cv2.putText(
                np.zeros((480, 640, 3), dtype=np.uint8),
                "Kamera Tidak Merespons",
                (50, 240),
                cv2.FONT_HERSHEY_SIMPLEX,
                1,
                (0, 0, 255),
                2
            )
            ret, buffer = cv2.imencode('.jpg', frame)
            frame = buffer.tobytes()
            yield (b'--frame\r\n'
                   b'Content-Type: image/jpeg\r\n\r\n' + frame + b'\r\n')
        return

    prev_frame_time_fire = 0
    prev_frame_time_drowsiness = 0

    while True:
        success, frame = cap.read()
        if not success:
            break

        # Calculate FPS and delay for fire detection
        new_frame_time_fire = time.time()
        delay_fire = (new_frame_time_fire - prev_frame_time_fire) * 1000  # in milliseconds
        fps_fire = 1 / (new_frame_time_fire - prev_frame_time_fire)
        prev_frame_time_fire = new_frame_time_fire

        # Calculate FPS and delay for drowsiness detection
        new_frame_time_drowsiness = time.time()
        delay_drowsiness = (new_frame_time_drowsiness - prev_frame_time_drowsiness) * 1000  # in milliseconds
        fps_drowsiness = 1 / (new_frame_time_drowsiness - prev_frame_time_drowsiness)
        prev_frame_time_drowsiness = new_frame_time_drowsiness

        # Make fire detections
        fire_results = fire_model(frame)
        fire_detected = False
        # Render fire detection results on the frame
        for result in fire_results:
            for box in result.boxes:
                x1, y1, x2, y2 = map(int, box.xyxy[0])
                label = fire_model.names[int(box.cls[0])]
                confidence = box.conf[0]
                if label == 'fire':
                    fire_detected = True
                    cv2.rectangle(frame, (x1, y1), (x2, y2), (0, 0, 255), 2)
                    cv2.putText(frame, f'{label} {confidence:.2f}', (x1, y1 - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.9, (0, 0, 255), 2)

        if fire_detected:
            if fire_detection_start is None:
                fire_detection_start = time.time()
            fire_detected_time = time.time() - fire_detection_start
            if fire_detected_time >= 3:
                insert_notification('Terdeteksi Api')
                buzzer('Hidup')
                fire_detection_start = None
                fire_detected_time = 0
        else:
            fire_detection_start = None
            fire_detected_time = 0

        # Make drowsiness detections
        drowsiness_results = drowsiness_model(frame)
        drowsiness_detected = False
        # Render drowsiness detection results on the frame
        for result in drowsiness_results:
            for box in result.boxes:
                x1, y1, x2, y2 = map(int, box.xyxy[0])
                label = drowsiness_model.names[int(box.cls[0])]
                confidence = box.conf[0]
                if label == 'drowsy':
                    drowsiness_detected = True
                    color = (0, 255, 255)
                    cv2.rectangle(frame, (x1, y1), (x2, y2), color, 2)
                    cv2.putText(frame, f'{label} {confidence:.2f}', (x1, y1 - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.9, color, 2)

        if drowsiness_detected:
            if drowsiness_detection_start is None:
                drowsiness_detection_start = time.time()
            drowsiness_detected_time = time.time() - drowsiness_detection_start
            if drowsiness_detected_time >= 3:
                insert_notification('Terdeteksi Kantuk')
                buzzer('Hidup')
                drowsiness_detection_start = None
                drowsiness_detected_time = 0
        else:
            drowsiness_detection_start = None
            drowsiness_detected_time = 0

        # Display FPS and delay for both detections on the frame
        cv2.putText(frame, f'Fire FPS: {fps_fire:.2f}', (10, 30), cv2.FONT_HERSHEY_SIMPLEX, 1, (255, 0, 0), 2)
        cv2.putText(frame, f'Drowsiness FPS: {fps_drowsiness:.2f}', (10, 70), cv2.FONT_HERSHEY_SIMPLEX, 1, (255, 0, 0), 2)
        cv2.putText(frame, f'Fire Delay: {delay_fire:.2f} ms', (10, 110), cv2.FONT_HERSHEY_SIMPLEX, 1, (255, 0, 0), 2)
        cv2.putText(frame, f'Drowsiness Delay: {delay_drowsiness:.2f} ms', (10, 150), cv2.FONT_HERSHEY_SIMPLEX, 1, (255, 0, 0), 2)

        # Record the video if recording is active
        if camera_index in recording and recording[camera_index]:
            if camera_index in video_writers:
                video_writers[camera_index].write(frame)

        ret, buffer = cv2.imencode('.jpg', frame)
        frame = buffer.tobytes()

        yield (b'--frame\r\n'
               b'Content-Type: image/jpeg\r\n\r\n' + frame + b'\r\n')

# Route to start recording
@app.route('/start_record/<camera_index>', methods=['POST'])
def start_record(camera_index):
    global recording, video_writers, start_time
    try:
        camera_index = int(camera_index)
    except ValueError:
        pass
    
    if camera_index not in recording or not recording[camera_index]:
        recording[camera_index] = True
        start_time[camera_index] = time.time()
        fourcc = cv2.VideoWriter_fourcc(*'avc1')
        filename = os.path.join(output_directory, f'recording_{camera_index}_{time.strftime("%Y%m%d-%H%M%S")}.mp4')
        insert_video(f'recording_{camera_index}_{time.strftime("%Y%m%d-%H%M%S")}.mp4')
        out = cv2.VideoWriter(filename, fourcc, 20.0, (640, 480))
        video_writers[camera_index] = out

    return '', 204

# Route to stop recording
@app.route('/stop_record/<camera_index>', methods=['POST'])
def stop_record(camera_index):
    global recording, video_writers, start_time
    try:
        camera_index = int(camera_index)
    except ValueError:
        pass
    
    if camera_index in recording and recording[camera_index]:
        recording[camera_index] = False
        if camera_index in video_writers:
            video_writers[camera_index].release()
            del video_writers[camera_index]
            del start_time[camera_index]
        # Copy the file to the backup directory
        latest_file = max([os.path.join(output_directory, f) for f in os.listdir(output_directory)], key=os.path.getctime)
        shutil.copy(latest_file, backup_directory)

    return '', 204

@app.route('/video_feed/<camera_index>')
def video_feed(camera_index):
    # Convert the camera index back to int if it's numeric
    try:
        camera_index = int(camera_index)
    except ValueError:
        pass
    return Response(generate_frames(camera_index), mimetype='multipart/x-mixed-replace; boundary=frame')

@app.route('/')
def beranda():
    active_cameras = detect_active_cameras()
    message = session.pop('message', None)
    success = session.pop('success', None)
    return render_template('beranda.php', active_cameras=active_cameras, message=message, success=success)

@app.route('/input_data', methods=['POST'])
def input_data():
    kameraRTSP = request.form['kameraRTSP']
    if kameraRTSP:
        insert_data(kameraRTSP)
        session['message'] = "Kamera RTSP Berhasil Ditambahkan"
        session['success'] = True
    else:
        session['message'] = "URL RTSP, Tidak Boleh Kosong!"
        session['success'] = False

    return redirect(url_for('beranda'))

@app.route('/dataNotification')
def dataNotification():
    conn = mysql.connector.connect(**db_config)
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM notifications ORDER BY id_notifications DESC")
    rows = cursor.fetchall()
    cursor.close()
    conn.close()
    return jsonify(rows)

@app.route('/hapus_catatan', methods=['POST'])
def hapus_catatan():
    conn = mysql.connector.connect(**db_config)
    cursor = conn.cursor(dictionary=True)
    cursor.execute("DELETE FROM notifications")
    conn.commit()
    cursor.close()
    conn.close()
    return redirect(url_for('index'))

def insert_notification(message):
    try:
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()
        timestamp = time.strftime('%Y-%m-%d %H:%M:%S')
        cursor.execute("INSERT INTO notifications (message, timestamp) VALUES (%s, %s)", (message, timestamp))
        conn.commit()
        cursor.close()
        conn.close()
        print(f"Inserted notification: {message} at {timestamp}")
    except mysql.connector.Error as err:
        print(f"Error: {err}")

def buzzer(status):
    conn = mysql.connector.connect(**db_config)
    cursor = conn.cursor()
    sql = ("UPDATE buzzer set status=%s WHERE id_buzzer=1")
    val = (status,)
    cursor.execute(sql, val)
    conn.commit()
    cursor.close()
    conn.close()

def buzzer_status():
    conn = mysql.connector.connect(**db_config)
    cursor = conn.cursor()
    cursor.execute("SELECT status FROM buzzer LIMIT 1")
    result = cursor.fetchone()[0]
    conn.close()
    return result

def buzzer_control():
    #GPIO.setmode(GPIO.BCM)
    buzzer1 = 17
    buzzer2 = 23
    #GPIO.setup(buzzer1, GPIO.OUT)
    #GPIO.setup(buzzer2, GPIO.OUT)
    while True:
        status = buzzer_status()
        if status == 'Hidup':
            print("Buzzer ON")
            #GPIO.output(buzzer1, GPIO.HIGH)
            #GPIO.output(buzzer2, GPIO.HIGH)
        else:
            print("Buzzer OFF")
            #GPIO.output(buzzer1, GPIO.LOW)
            #GPIO.output(buzzer2, GPIO.LOW)
        time.sleep(1)
    #GPIO.cleanup()

def insert_video(namaVideo):
    try:
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()
        cursor.execute("INSERT INTO video (video) VALUES (%s)", (namaVideo,))
        conn.commit()
        cursor.close()
        conn.close()
        print(f"Inserted video: {namaVideo}")
    except mysql.connector.Error as err:
        print(f"Error: {err}")

if __name__ == '__main__':
    buzzer_thread = Thread(target=buzzer_control, daemon=True)
    buzzer_thread.start()
    app.run(debug=True)
