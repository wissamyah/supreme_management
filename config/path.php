<?php
class Path {
    private static $basePath = null;

    public static function getBasePath() {
        if (self::$basePath === null) {
            $path = dirname($_SERVER['PHP_SELF']);
            $positions = ['/pages/customers', '/pages/users', '/pages/orders', '/pages/inventory','/pages/suppliers', '/pages', '/api', '/auth'];
            
            foreach ($positions as $pos) {
                if (strpos($path, $pos) !== false) {
                    $path = str_replace($pos, '', $path);
                }
            }
            self::$basePath = rtrim($path, '/');
        }
        return self::$basePath;
    }

    public static function url($path = '') {
        return self::getBasePath() . '/' . ltrim($path, '/');
    }
}