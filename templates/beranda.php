<?php
session_start();
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Smart Home Surveillance</title>
    <meta name="theme-color" content="#001b2f">
    <link rel="icon" type="image/png" sizes="718x734" href="{{ url_for('static', filename='assets/img/logo.png') }}">
    <link rel="icon" type="image/png" sizes="718x734" href="{{ url_for('static', filename='assets/img/logo.png') }}" media="(prefers-color-scheme: dark)">
    <link rel="icon" type="image/png" sizes="718x734" href="{{ url_for('static', filename='assets/img/logo.png') }}">
    <link rel="icon" type="image/png" sizes="718x734" href="{{ url_for('static', filename='assets/img/logo.png') }}" media="(prefers-color-scheme: dark)">
    <link rel="icon" type="image/png" sizes="718x734" href="{{ url_for('static', filename='assets/img/logo.png') }}">
    <link rel="icon" type="image/png" sizes="718x734" href="{{ url_for('static', filename='assets/img/logo.png') }}">
    <link rel="icon" type="image/png" sizes="718x734" href="{{ url_for('static', filename='assets/img/logo.png') }}">
    <link rel="stylesheet" href="{{ url_for('static', filename='assets/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=ADLaM+Display&amp;display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Alfa+Slab+One&amp;display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Anton&amp;display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Rubik+Bubbles&amp;subset=cyrillic,cyrillic-ext,hebrew,latin-ext&amp;display=swap">
    <link rel="stylesheet" href="{{ url_for('static', filename='assets/fonts/fontawesome-all.min.css') }}">
    <link rel="stylesheet" href="{{ url_for('static', filename='assets/fonts/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ url_for('static', filename='assets/fonts/fontawesome5-overrides.min.css') }}">
    <link rel="stylesheet" href="{{ url_for('static', filename='assets/css/Scrollspy.css') }}">
</head>
<body style="background: rgb(13,110,253);">
<section id="Header">
        <nav class="navbar navbar-expand fixed-top bg-primary pt-3 ps-1">
            <div class="container-fluid"><a class="navbar-brand" href="#"><img class="img-fluid" src="{{ url_for('static', filename='assets/img/logo.png') }}" width="45px">&nbsp;<span class="text-white" style="font-size: 15px;font-family: 'ADLaM Display', serif;">Smart Home Surveillance</span></a><button data-bs-toggle="collapse" class="navbar-toggler" data-bs-target="#navcol-1"><span class="visually-hidden">Toggle navigation</span><span class="navbar-toggler-icon"></span></button>
                <div class="collapse navbar-collapse" id="navcol-1">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link text-white" href="#" onclick="reloadPage()"><i class="fa fa-refresh"></i></a></li>
                        <li class="nav-item"><a class="nav-link text-white" data-bs-toggle="modal" data-bs-target="#KeluarModal" href="#"><i class="fas fa-sign-out-alt"></i>&nbsp;Keluar</a></li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="modal fade" role="dialog" tabindex="-1" id="KeluarModal">
            <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><strong>Konfirmasi</strong></h5><button class="btn-close" type="button" aria-label="Close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <h1 style="font-size: 45px;"><i class="fas fa-exclamation-triangle"></i></h1>
                        <p>Apakah anda ingin keluar ?</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-light btn-sm border rounded" type="button" data-bs-dismiss="modal"><i class="fas fa-times"></i>&nbsp;Tidak</button>
                        <a class="btn btn-dark btn-sm" href="https://smarthomesurveillance-web.ngrok.app/SHS_web/logout.php" role="button"><i class="fas fa-check"></i>&nbsp;Ya, saya ingin keluar</a></div>
                </div>
            </div>
        </div>
    </section>
    <section id="Main" style="margin-top: 30%;margin-bottom: 30%;">
    <div class="container">
        {% for camera in active_cameras %}
        <div class="row pt-2">
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <h5 class="text-center">{{ camera[1] }}</h5>
                        <img src="{{ url_for('video_feed', camera_index=camera[0]) }}" width="100%">

                        <div class="row">
                            <div class="col-12">
                                <div class="timer"><p class="lead"><b>Durasi: </b><span id="time-{{ camera[0] }}">00:00:00</span></p></div>
                                <p class="text-end">
                                <form action="{{ url_for('start_record', camera_index=camera[0]) }}" method="post" style="display:inline;">
                                    <button id="start-btn-{{ camera[0] }}" class="btn btn-dark btn-sm" type="submit"><span class="text-truncate"><i class="fas fa-record-vinyl"></i>&nbsp;Mulai Rekam</span></button>
                                </form>
                                <form action="{{ url_for('stop_record', camera_index=camera[0]) }}" method="post" style="display:inline;">
                                <button id="stop-btn-{{ camera[0] }}" class="btn btn-danger btn-sm" type="submit" disabled><span class="text-truncate"><i class="fas fa-stop"></i>&nbsp;Berhenti Rekam</span></button>
                                </form>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {% endfor %}
    </div>
    <div class="container pt-3">
            <div class="row">
                <div class="col-8">
                    <h5 class="text-white" style="font-family: 'ADLaM Display', serif;">Rekaman Catatan</h5>
                </div>
                <div class="col-4 align-self-end pb-2">
                    <form id="formCatatan"><button class="btn btn-warning btn-sm" id="hapusCatatan" name="hapusCatatan" type="submit"><i class="fas fa-trash"></i>&nbsp;Bersihkan</button></form>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-9 col-md-9 col-xl-10">
                            <div class="scrollspy-example" data-spy="scroll" data-target="#list-example" data-offset="0" style="overflow: scroll;height: 20vh;">
                                <ul class="list-group" id="data-notification">
                                </ul>
                            </div>
                        </div><script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    document.body.onload = function () {
        $('[data-spy="scroll"]').each(function () {
            var $spy = $(this).scrollspy('refresh')
        });
    };
</script>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section id="Navbar">
        <nav class="navbar navbar-expand fixed-bottom bg-black mt-0 mb-0 pt-0" data-bs-theme="dark" style="background: rgb(0,27,47);border-color: rgb(0,27,47);color: rgb(0,27,47);">
            <div class="container-fluid"><button data-bs-toggle="collapse" class="navbar-toggler" data-bs-target="#navcol-2"><span class="visually-hidden">Toggle navigation</span><span class="navbar-toggler-icon"></span></button>
                <div class="collapse navbar-collapse" id="navcol-2">
                    <ul class="navbar-nav mx-auto mt-0 pt-1">
                        <li class="nav-item pb-0 me-3"><a class="nav-link active text-center" href="https://smarthomesurveillance-flask.ngrok.app"><i class="fas fa-home" style="font-size: 20px;"></i>
                                <p style="font-size: 11px;">Beranda</p>
                            </a></li>
                        <li class="nav-item pb-0 me-3"><a class="nav-link text-center" href="#" data-bs-toggle="modal" data-bs-target="#CameraModal"><i class="fas fa-video" style="font-size: 20px;"></i>
                                <p class="text-truncate" style="font-size: 11px;">Kamera RTSP</p>
                            </a></li>
                        <li class="nav-item pb-0 me-3"><a class="nav-link text-center" href="https://smarthomesurveillance-web.ngrok.app/SHS_web/video.php"><i class="fas fa-film" style="font-size: 20px;"></i>
                                <p style="font-size: 11px;">Video</p>
                            </a></li>
                        <li class="nav-item pb-0 me-3"><a class="nav-link text-center" href="https://smarthomesurveillance-web.ngrok.app/SHS_web/buzzer.php"><i class="fas fa-volume-up" style="font-size: 20px;"></i>
                                <p style="font-size: 11px;">Buzzer</p>
                            </a></li>
                        <li class="nav-item pb-0"><a class="nav-link text-center" href="https://smarthomesurveillance-web.ngrok.app/SHS_web/profil.php"><i class="fas fa-user" style="font-size: 20px;"></i>
                                <p style="font-size: 11px;">Profil</p>
                            </a></li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="modal fade" role="dialog" tabindex="-1" id="CameraModal">
            <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><strong>Kamera RTSP</strong></h5><button class="btn-close" type="button" aria-label="Close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="myForm">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col">
                                    <label for="kameraRTSP" class="form-label">URL RTSP :</label>
                                    <input class="form-control form-control-sm" type="text" id="kameraRTSP" name="kameraRTSP" placeholder="rtsp://username:password@IpAddress:Port">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col text-center">
                                    <hr><a class="btn btn-outline-dark btn-sm" role="button" href="https://smarthomesurveillance-web.ngrok.app/SHS_web/kamera_rtsp.php"><i class="fa fa-eye"></i>&nbsp;Lihat Data Kamera RTSP</a>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-light btn-sm border rounded" type="reset"><i class="fa fa-refresh"></i>&nbsp;Reset</button>
                            <button class="btn btn-dark btn-sm" type="submit"><i class="fas fa-save"></i>&nbsp;Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
                    {% if message %}
                    <div class="toast-container position-fixed top-0 end-0 p-3">
                        <div id="myToast" class="toast align-items-center text-white {{ 'bg-success' if success else 'bg-danger' }} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                            <div class="d-flex">
                                <div class="toast-body text-white">
                                    <strong>{{ message }}</strong>
                                </div>
                                <button type="button" class="btn-close btn-close-dark me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                             </div>
                        </div>
                    </div>
                    {% endif %}
    </section>
    <script src="{{ url_for('static', filename='assets/js/jquery.min.js') }}"></script>
    <script src="{{ url_for('static', filename='assets/bootstrap/js/bootstrap.min.js') }}"></script>
    <script src="{{ url_for('static', filename='assets/js/bs-init.js') }}"></script>
    <script>
        const startTimes = {};
        const timers = {};

        // Update the recording time for each camera
        function updateRecordingTime(cameraIndex) {
            const currentTime = new Date();
            const startTime = startTimes[cameraIndex];
            const elapsedTime = Math.floor((currentTime - startTime) / 1000);

            const hours = String(Math.floor(elapsedTime / 3600)).padStart(2, '0');
            const minutes = String(Math.floor((elapsedTime % 3600) / 60)).padStart(2, '0');
            const seconds = String(elapsedTime % 60).padStart(2, '0');

            document.getElementById(`time-${cameraIndex}`).textContent = `${hours}:${minutes}:${seconds}`;
        }

        // Start recording time tracking
        function startRecordingTime(cameraIndex) {
            startTimes[cameraIndex] = new Date();
            timers[cameraIndex] = setInterval(() => updateRecordingTime(cameraIndex), 1000);
        }

        // Stop recording time tracking
        function stopRecordingTime(cameraIndex) {
            clearInterval(timers[cameraIndex]);
            document.getElementById(`time-${cameraIndex}`).textContent = '00:00:00';
        }

        // Attach event listeners to the start and stop buttons
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(event) {
                event.preventDefault(); // Prevent the form from submitting immediately

                const formAction = event.target.action;
                const cameraIndex = formAction.match(/(\d+)$/)[0];

                if (formAction.includes('start_record')) {
                    startRecordingTime(cameraIndex);
                    document.getElementById(`start-btn-${cameraIndex}`).disabled = true;
                    document.getElementById(`stop-btn-${cameraIndex}`).disabled = false;

                    // After changing button states, submit the form
                    form.submit();
                } else if (formAction.includes('stop_record')) {
                    stopRecordingTime(cameraIndex);
                    document.getElementById(`start-btn-${cameraIndex}`).disabled = false;
                    document.getElementById(`stop-btn-${cameraIndex}`).disabled = true;

                    // After changing button states, submit the form
                    form.submit();
                }
            });
        });

        function reloadPage() {
            location.reload();
        }

        document.addEventListener('DOMContentLoaded', function () {
        var myToast = document.getElementById('myToast');
        if (myToast) {
            var toast = new bootstrap.Toast(myToast);
            toast.show();
        }
        });

    document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('myForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            const kameraRTSP = document.getElementById('kameraRTSP').value;
            fetch('/input_data', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'kameraRTSP': kameraRTSP
                })
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    return response.text();
                }
            });
        });
    } else {
        console.error("Form tidak ditemukan!");
        }
    });

$(document).ready(function() {
    fetchDataNotification();
    setInterval(fetchDataNotification, 1000);
});


function fetchDataNotification() {
    $.ajax({
        url: "/dataNotification",
        method: "GET",
        success: function(dataNotification) {
            let list = $("#data-notification");
            list.empty();
            if (dataNotification.length === 0) {
                list.append("<h3 class='text-center text-secondary'>Data Masih Kosong</h3>");
            } else {
                dataNotification.forEach(function(result) {
                    list.append(`
                        <li class='list-group-item'>
                            <div class='row'>
                                <div class='col'>
                                    <span>${result.message}</span>
                                </div>
                                <div class='col text-end'>
                                    <span class='text-truncate'>
                                        <sub>${result.timestamp}</sub>
                                    </span>
                                </div>
                            </div>
                        </li>
                    `);
                });
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formCatatan');
    if (form) {
        form.addEventListener('submit', function(event) {
            const hapusCatatan = document.getElementById('hapusCatatan').value;
            fetch('/hapus_catatan', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'hapusCatatan': hapusCatatan
                })
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    return response.text();
                }
            });
        });
    } else {
        console.error("Form tidak ditemukan!");
        }
    });

    </script>
</body>
</html>
