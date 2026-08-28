<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $this->apk[0]->nama_apk; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1663B8;
            --secondary-color: #0F6CCD;
            --dark-color: #071E3D;
            --light-color: #EAF0F7;
            --text-color: #333;
            --text-muted: #8E9AB4;
        }

        body {
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
        }

        .atlantis-bg {
            background: linear-gradient(135deg, #1f4287 0%, #071e3d 100%);
        }

        .login-container {
            min-height: 100vh;
        }

        .login-side-img {
            background: linear-gradient(135deg, #1f4287 0%, #071e3d 100%);
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .login-side-img::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: linear-gradient(135deg, rgba(31, 66, 135, 0.8) 0%, rgba(7, 30, 61, 0.7) 100%);
        }

        .form-floating>label {
            color: #8E9AB4;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(22, 99, 184, 0.25);
        }

        .atlantis-btn {
            background: linear-gradient(135deg, #0f6ccd 0%, #1663b8 100%);
            color: white;
            border: none;
            padding: 0.75rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .atlantis-btn:hover {
            background: linear-gradient(135deg, #1663b8 0%, #0f6ccd 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(15, 108, 205, 0.3);
        }

        .atlantis-divider {
            display: flex;
            align-items: center;
            text-align: center;
            color: var(--text-muted);
            margin: 1.5rem 0;
        }

        .atlantis-divider::before,
        .atlantis-divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e0e6ed;
        }

        .atlantis-divider::before {
            margin-right: 1rem;
        }

        .atlantis-divider::after {
            margin-left: 1rem;
        }

        .social-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin: 0 0.5rem;
            transition: all 0.3s ease;
        }

        .social-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.1);
        }

        .password-toggle {
            position: absolute;
            top: 1rem;
            right: 1rem;
            cursor: pointer;
            color: #8E9AB4;
            background: none;
            border: none;
        }

        .content-wrapper {
            position: relative;
            z-index: 5;
        }

        @media (max-width: 768px) {
            .login-side-img {
                min-height: 200px;
            }
        }

        /* Background gradient lembut untuk sisi kiri */
        .login-side-img {
            background: linear-gradient(135deg, #007bff 0%, #1f4287 100%);
            min-height: 100vh;
            color: #fff;
        }

        /* Accent bubble lembut */
        .login-side-img::before,
        .login-side-img::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.3;
        }

        .login-side-img::before {
            top: 10%;
            left: 15%;
            width: 200px;
            height: 200px;
            background: #00b4d8;
        }

        .login-side-img::after {
            bottom: 10%;
            right: 15%;
            width: 250px;
            height: 250px;
            background: #0096c7;
        }

        /* Form container */
        .login-form-box {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }

        /* Password toggle button */
        .password-toggle {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
        }

        /* Responsiveness: stack layout di mobile */
        @media (max-width: 991px) {
            .login-side-img {
                min-height: 250px;
                border-radius: 0 0 40px 40px;
                text-align: center;
            }
        }
    </style>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/atlantis.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/icon_bootsrap.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/sweetalert2.css') ?>">
    <link rel="icon" type="image/x-icon" href="<?php echo base_url(); ?>assets/images/favicon.ico" />

</head>

<body>
    <div class="container-fluid p-0">
        <div class="row g-0 login-container">

            <!-- Left side - Banner -->
            <div class="col-lg-6 position-relative d-flex align-items-center justify-content-center login-side-img">
                <div class="content-wrapper text-center text-white p-4 p-md-5 w-100">
                    <h2 class="fw-bold mb-3">Selamat Datang</h2>
                    <p class="mb-4 opacity-75">Platform Pengelolaan Bisnis Bimbel Terpadu</p>
                </div>
            </div>

            <!-- Right side - Form -->
            <div class="col-lg-6 d-flex align-items-center justify-content-center p-4 p-md-5 bg-light">
                <div class="login-form-box w-100" style="max-width: 500px;">
                    <div class="text-center mb-4">
                        <img src="<?php echo base_url('uploads/logo/' . $this->apk[0]->logo); ?>" alt="Logo" class="rounded-circle mb-4 mt-5" style="width: 25%;">
                        <h3 class="fw-semibold mb-2 mt-4"><?php echo $this->apk[0]->nama_apk; ?></h3>
                        <p class="text-muted">Login untuk mengakses sistem</p>
                    </div>

                    <?php if (isset($_SESSION['login_error'])) : ?>
                        <div class="alert alert-danger">
                            <?php echo $_SESSION['login_error'];
                            unset($_SESSION['login_error']); ?>
                        </div>
                    <?php endif; ?>

                    <form id="form_login" method="POST">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="username" name="username" placeholder="name@example.com" required>
                            <label for="username">Username</label>
                        </div>

                        <div class="form-floating mb-3 position-relative">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                            <label for="password">Password</label>
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>

                        <button type="submit" class="btn w-100 py-2 mb-3" style="border-radius: 10px; 
           background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%);
           color: #fff;
           border: none;
           font-weight: 600;
           letter-spacing: 0.5px;
           transition: all 0.3s ease;">
                            Login
                        </button>

                    </form>
                </div>

            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }
    </script>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="<?php echo base_url('assets/js/core/jquery.3.2.1.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/sweetalert2.js') ?>"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('#form_login').on('submit', function(event) {
                event.preventDefault();
                $.ajax({
                    type: "POST",
                    url: '<?php echo site_url('Login/aksi_login'); ?>',
                    data: {
                        username: $('#username').val(),
                        password: $('#password').val()
                    },
                    dataType: "json",
                    success: function(data) {
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-right',
                            showConfirmButton: false,
                            timer: 3000
                        });
                        Toast.fire({
                            type: 'success',
                            title: 'Akses diterima'
                        })
                        console.log(data.role);
 
                            setTimeout(function() {
                                window.location.href =
                                    '<?php echo site_url('Dashboard'); ?>';
                                window.clearTimeout();
                            }, 1000);   

                    },
                    error: function(request, status, error) {
                        console.log(request.responseText);
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-right',
                            showConfirmButton: false,
                            timer: 3000
                        });
                        Toast.fire({
                            type: 'error',
                            title: 'Akses ditolak, Username atau Password Salah'
                        })
                    }

                });

            });


        })
    </script>
</body>

</html>