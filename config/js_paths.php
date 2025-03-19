<?php
// This file creates a JavaScript object with proper application paths
require_once __DIR__ . '/path.php';

// Function to output JS path variables for use in scripts
function outputJsPaths() {
    echo '<script>';
    echo 'const appPaths = {';
    echo 'base: "' . Path::url('') . '",';
    echo 'api: "' . Path::url('api') . '",';
    echo 'assets: "' . Path::url('assets') . '",';
    echo 'auth: "' . Path::url('auth') . '"';
    echo '};';
    echo '</script>';
} 