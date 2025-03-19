<?php
session_start();
require_once '../../config/path.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . Path::url('auth/login.php'));
    exit();
}

require_once '../../includes/header.php';
require_once '../../includes/topbar.php';
require_once '../../includes/sidebar.php';
?>

<div class="content-wrapper bg-light">
    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-white p-4 rounded-top" style="background: #343a40">
                    <div class="d-flex align-items-center">
                        <div class="text-white p-3 me-3">
                            <i class="bi bi-person-circle fs-4"></i>
                        </div>
                        <h3 class="mb-0">Profile Settings</h3>
                    </div>
                </div>
                
                <div class="bg-white shadow-sm rounded-bottom p-4">
                    <form id="profileForm" class="needs-validation" novalidate>
                        <input type="hidden" id="userId" value="<?php echo $_SESSION['user_id']; ?>">
                        
                        <div class="mb-4">
                            <label class="form-label text-muted fw-bold mb-2">Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-1">
                                    <i class="bi bi-person text-dark"></i>
                                </span>
                                <input type="text" 
                                       class="form-control bg-light border-1 py-2" 
                                       id="username" 
                                       name="username" 
                                       required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-muted fw-bold mb-2">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-1">
                                    <i class="bi bi-envelope text-dark"></i>
                                </span>
                                <input type="email" 
                                       class="form-control bg-light border-1 py-2" 
                                       id="email" 
                                       name="email" 
                                       required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-muted fw-bold mb-2">Change Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-1">
                                    <i class="bi bi-lock text-dark"></i>
                                </span>
                                <input type="password" 
                                       class="form-control bg-light border-1 py-2" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Leave blank to keep current password">
                                <button class="btn border-1" type="button" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text text-muted">Minimum 8 characters</div>
                        </div>

                        <div class="d-grid gap-2 mt-5">
                            <button type="submit" class="btn btn-lg text-white" style="background: #343a40">
                                <i class="bi bi-check2-circle me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom Toast Container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="toastContainer"></div>
</div>

<style>
.input-group-text {
    border-right: 0;
}

.form-control:focus {
    box-shadow: none;
    border-color: #343a40;
    background-color: #fff !important;
}

.input-group .form-control:focus ~ .input-group-text {
    border-color: #343a40;
    background-color: #fff;
}

.custom-toast {
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Define base URLs using PHP
    const apiBaseUrl = '<?php echo Path::url('api'); ?>';
    
    const userId = document.getElementById('userId').value;
    const profileForm = document.getElementById('profileForm');
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');

    // Toggle password visibility
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        togglePassword.innerHTML = type === 'password' ? 
            '<i class="bi bi-eye"></i>' : 
            '<i class="bi bi-eye-slash"></i>';
    });

    // Input animation
    document.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('focus', () => {
            input.closest('.input-group').querySelector('.input-group-text').style.backgroundColor = '#fff';
        });

        input.addEventListener('blur', () => {
            if (!input.value) {
                input.closest('.input-group').querySelector('.input-group-text').style.backgroundColor = '#f8f9fa';
            }
        });
    });

    // Fetch user data
    fetch(`${apiBaseUrl}/users/read.php?id=${userId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('username').value = data.username;
            document.getElementById('email').value = data.email;
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                toast: true,
                icon: 'error',
                title: 'Failed to load profile data',
                position: 'bottom-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        });

    // Handle form submission
    profileForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
        
        const formData = {
            id: userId,
            username: document.getElementById('username').value,
            email: document.getElementById('email').value
        };

        const password = document.getElementById('password').value;
        if (password) {
            formData.password = password;
        }

        fetch(`${apiBaseUrl}/users/update.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update session first
                fetch(`${apiBaseUrl}/users/update_session.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        username: document.getElementById('username').value
                    })
                });

                // Update sidebar username
                document.querySelector('.sidebar-user h6').textContent = document.getElementById('username').value;

                // Show success toast
                Swal.fire({
                    toast: true,
                    icon: 'success',
                    title: 'Profile updated successfully',
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });

                if (password) {
                    document.getElementById('password').value = '';
                }
            } else {
                Swal.fire({
                    toast: true,
                    icon: 'error',
                    title: data.message || 'Failed to update profile',
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                toast: true,
                icon: 'error',
                title: 'Failed to update profile',
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-check2-circle me-2"></i>Save Changes';
        });
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>