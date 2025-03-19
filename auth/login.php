<?php
session_start();
require_once '../config/path.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . Path::url('index.php'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'><circle cx='8' cy='8' r='8' fill='white'/><g transform='translate(2.5,2.5) scale(0.7)'><path fill='%23333' d='M14.763.075A.5.5 0 0 1 15 .5v15a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5V14h-1v1.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V10a.5.5 0 0 1 .342-.474L6 7.64V4.5a.5.5 0 0 1 .276-.447l8-4a.5.5 0 0 1 .487.022ZM6 8.694 1 10.36V15h5V8.694ZM7 15h2v-1.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5V15h2V1.309l-7 3.5V15Z'/></g></svg>">
    <title>Supreme Rice Mills ltd.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #475569;
            --primary-dark: #334155;
        }
        
        body {
            background: url('<?php echo Path::url('assets/img/ricefield.jpg'); ?>') no-repeat center center fixed;
            background-size: cover; /* Ensures the image covers the entire background */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .login-container {
            max-width: 420px;
            width: 90%;
            margin: 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
            overflow: hidden;
            position: relative;
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            padding: 2.5rem 2rem;
            text-align: center;
            color: white;
            position: relative;
        }

        .login-header::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: 0;
            right: 0;
            height: 50px;
            background: white;
            clip-path: ellipse(75% 100% at 50% 100%);
        }

        .brand-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        .brand-logo i {
            font-size: 2.5rem;
            color: white;
        }

        .login-header h1 {
            font-size: 1.75rem;
            font-weight: 600;
            margin: 0;
        }

        .login-form {
            padding: 2rem;
        }

        .form-floating {
            margin-bottom: 1.5rem;
        }

        .form-floating .form-control {
            border-radius: 8px;
            border: 2px solid #e5e7eb;
            padding: 1rem 0.75rem;
            height: auto;
            font-size: 1rem;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(71, 85, 105, 0.1);
        }

        .form-floating label {
            padding: 1rem 0.75rem;
        }

        .btn-login {
            background: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 0.875rem;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.2s;
        }

        .btn-login:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6b7280;
            z-index: 10;
        }

        .swal2-confirm {
            background-color: var(--primary-color) !important;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .shake {
            animation: shake 0.3s ease-in-out;
        }
    </style>
    <?php require_once '../config/js_paths.php'; outputJsPaths(); ?>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="brand-logo">
                <i class="bi bi-building"></i>
            </div>
            <h1>Supreme Rice Mills LTD</h1>
            <p class="mb-0">MANAGEMENT PORTAL</p>
        </div>
        
        <form id="loginForm" class="login-form">
            <div class="form-floating">
                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                <label for="username">Username</label>
            </div>
            
            <div class="form-floating position-relative">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                <label for="password">Password</label>
                <span class="password-toggle" onclick="togglePassword()">
                    <i class="bi bi-eye-slash"></i>
                </span>
            </div>

            <button type="submit" class="btn btn-login btn-primary w-100 mb-3">
                <span class="d-flex align-items-center justify-content-center">
                    <span id="btnText">Sign In</span>
                    <span id="btnSpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
                </span>
            </button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.replace('bi-eye-slash', 'bi-eye');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.replace('bi-eye', 'bi-eye-slash');
            }
        }

        document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const btnText = document.getElementById('btnText');
        const btnSpinner = document.getElementById('btnSpinner');
        const loginBtn = document.querySelector('.btn-login');
        
        // Disable button and show spinner
        loginBtn.disabled = true;
        loginBtn.style.backgroundColor = '#3A4657';
        btnText.textContent = 'Signing in...';
        btnSpinner.classList.remove('d-none');
        
        const formData = new FormData(this);
        
        fetch('login_process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'You have successfully signed in',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true,
                    didClose: () => {
                        window.location.href = appPaths.base + '/index.php';
                    }
                });
            } else {
                // Show error and shake animation
                loginBtn.classList.add('shake');
                setTimeout(() => loginBtn.classList.remove('shake'), 300);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Login Failed',
                    text: data.message || 'Invalid credentials',
                    confirmButtonColor: '#2563eb'
                });
                
                // Reset button state only on failure
                loginBtn.disabled = false;
                btnText.textContent = 'Sign In';
                btnSpinner.classList.add('d-none');
                loginBtn.style.backgroundColor = '#3A4657';
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred during login',
                confirmButtonColor: '#2563eb'
            });
            
            // Reset button state on error
            loginBtn.disabled = false;
            btnText.textContent = 'Sign In';
            btnSpinner.classList.add('d-none');
            loginBtn.style.backgroundColor = '#3A4657';
        });
    });
    </script>
</body>
</html>