<?php
class Path {
    private static $basePath = null;

    public static function getBasePath() {
        if (self::$basePath === null) {
            $serverName = $_SERVER['SERVER_NAME'];
            
            // Check if we're on the production server
            if ($serverName === 'app.supremericemills.com') {
                // We're on production - use root path
                self::$basePath = '';
            } else {
                // We're on local or other environment
                $path = dirname($_SERVER['PHP_SELF']);
                $positions = ['/pages/customers', '/pages/users', '/pages/orders', '/pages/inventory','/pages/suppliers', '/pages', '/api', '/auth'];
                
                foreach ($positions as $pos) {
                    if (strpos($path, $pos) !== false) {
                        $path = str_replace($pos, '', $path);
                    }
                }
                self::$basePath = rtrim($path, '/');
            }
        }
        return self::$basePath;
    }

    public static function url($path = '') {
        return self::getBasePath() . '/' . ltrim($path, '/');
    }
}