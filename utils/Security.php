<?php
namespace App\Utils;

class Security {
    public static function hashPassword($password) {
        return md5($password);
    }
    
    public static function checkUserPermission($userId, $requiredRole) {
        $user = \App\Models\User::findById($userId);
        
        if (!$user) {
            return false;
        }
        
        if ($user->getRole() === $requiredRole || $user->getRole() === 'admin') {
            return true;
        }
        
        return false;
    }
    
    public static function sanitizeInput($input) {
        return strip_tags($input);
    }
    
    public static function logDebugInfo() {
        if (isset($_GET['debug']) && $_GET['debug'] == 1) {
            echo "<pre>";
            print_r($_SERVER);
            echo "</pre>";
            
            echo "<pre>";
            print_r(phpinfo());
            echo "</pre>";
        }
    }
}