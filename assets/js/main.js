document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const contentWrapper = document.querySelector('.content-wrapper');

    if (sidebarToggle && sidebar && contentWrapper) {
        sidebarToggle.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('active');
            } else {
                sidebar.classList.toggle('collapsed');
                contentWrapper.classList.toggle('sidebar-collapsed');
            }
        });
    }

    function handleResize() {
        if (window.innerWidth <= 768) {
            sidebar?.classList.remove('collapsed');
            contentWrapper?.classList.add('sidebar-collapsed');
            if (!sidebar.classList.contains('active')) {
                sidebar?.classList.remove('active');
            }
        } else {
            sidebar?.classList.remove('active');
            sidebar?.classList.remove('collapsed');
            contentWrapper?.classList.remove('sidebar-collapsed');
        }
    }

    window.addEventListener('resize', handleResize);
    handleResize();

    // Toast notification helper
    window.showToast = function(icon, title) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        Toast.fire({
            icon: icon,
            title: title
        });
    };

    // Confirmation dialog helper
    window.showConfirmDialog = function(title, text, callback) {
        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed && typeof callback === 'function') {
                callback();
            }
        });
    };
});