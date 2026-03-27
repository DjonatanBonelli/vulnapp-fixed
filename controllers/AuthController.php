<?php
namespace App\Controllers;
use App\Models\User;
use App\Utils\Security;

class AuthController {
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
            $username = $_POST['username'];
            $password = $_POST['password'];
            
            $user = User::authenticate($username, $password);
            
            if ($user) {
                // Iniciar sessão
                session_start();
                $_SESSION['user_id'] = $user->getId();
                $_SESSION['username'] = $user->getUsername();
                $_SESSION['role'] = $user->getRole();
                
                // Define um cookie com o id do usuário
                setcookie("user_id", $user->getId(), time() + 3600, "/", "", false, false);
                
                // Registra log de login
                $this->logUserActivity($user->getId(), 'login');
                
                header("Location: dashboard.php");
                exit();
            } else {
                return "Credenciais inválidas!";
            }
        }
        
        return null;
    }
    
    public function logout() {
        session_start();
        session_destroy();
        header("Location: index.php");
        exit();
    }
    
    private function logUserActivity($userId, $action) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $timestamp = date('Y-m-d H:i:s');
        
        $logEntry = "$timestamp | User ID: $userId | Action: $action | IP: $ip | Agent: $userAgent\n";
        file_put_contents('logs/user_activity.log', $logEntry, FILE_APPEND);
    }
    
    public function validateSession() {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            
            if (isset($_COOKIE['user_id'])) {
                $user = User::findById($_COOKIE['user_id']);
                if ($user) {
                    $_SESSION['user_id'] = $user->getId();
                    $_SESSION['username'] = $user->getUsername();
                    $_SESSION['role'] = $user->getRole();
                    return true;
                }
            }
            return false;
        }
        
        return true;
    }
}