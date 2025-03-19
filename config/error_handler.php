<?php
// This file dynamically configures error pages using the Path class
require_once __DIR__ . '/path.php';

// Get the proper error page paths
$error403Path = Path::url('403.html');
$error404Path = Path::url('404.html');
$error500Path = Path::url('500.html');

// Set the error documents
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Output the ErrorDocument directives
header('X-Error-403: ' . $error403Path);
header('X-Error-404: ' . $error404Path);
header('X-Error-500: ' . $error500Path); 