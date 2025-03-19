<!-- includes/header.php -->
<?php
$pathHeaderIncluded = true;
// Determine the relative path to config
$configPath = '';
if (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) {
    $configPath = '../../config/';
} elseif (strpos($_SERVER['PHP_SELF'], '/auth/') !== false) {
    $configPath = '../config/';
} else {
    $configPath = 'config/';
}

// Include path class if not already included
if (!class_exists('Path')) {
    require_once $configPath . 'path.php';
}

// Include JS paths
require_once $configPath . 'js_paths.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'><circle cx='8' cy='8' r='8' fill='white'/><g transform='translate(2.5,2.5) scale(0.7)'><path fill='%23333' d='M14.763.075A.5.5 0 0 1 15 .5v15a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5V14h-1v1.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V10a.5.5 0 0 1 .342-.474L6 7.64V4.5a.5.5 0 0 1 .276-.447l8-4a.5.5 0 0 1 .487.022ZM6 8.694 1 10.36V15h5V8.694ZM7 15h2v-1.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5V15h2V1.309l-7 3.5V15Z'/></g></svg>">
    <title>Supreme Rice Mills ltd.</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo Path::url('assets/css/style.css'); ?>" rel="stylesheet">
    
    <?php outputJsPaths(); ?>
</head>
<body></body>