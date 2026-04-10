<?php
namespace App\Controllers;
use App\Models\User;
use App\Utils\Security;

class AuthController {
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
            // inicia sessão cedo para trackear tentativas
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            // limite de tentativas por sessão (mitiga brute-force)
            $now = time();
            $windowSeconds = 5 * 60;
            $maxAttempts = 8;
            if (!isset($_SESSION['_login_attempts']) || !is_array($_SESSION['_login_attempts'])) {
                $_SESSION['_login_attempts'] = [];
            }
            $_SESSION['_login_attempts'] = array_values(array_filter(
                $_SESSION['_login_attempts'],
                fn($ts) => is_int($ts) && ($now - $ts) <= $windowSeconds
            ));
            if (count($_SESSION['_login_attempts']) >= $maxAttempts) {
                return "Muitas tentativas. Tente novamente em alguns minutos.";
            }
            // implementada checagem de CSRF com token
            if (isset($_POST['_csrf']) && !Security::verifyCsrfToken($_POST['_csrf'])) {
            return "Requisição inválida.";
        } 

            // sanitizações
            $username = (string)($_POST['username'] ?? '');
            $password = (string)($_POST['password'] ?? '');
            
            $user = User::authenticate($username, $password);
            
            if ($user) {
                // evita session fixation
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user->getId();
                $_SESSION['username'] = $user->getUsername();
                $_SESSION['role'] = $user->getRole();
                // identidade da sessão é controlada pelo servidor php agora, não é mais autodeclarada
                $_SESSION['_login_attempts'] = [];
                
                // Registra log de login
                $this->logUserActivity($user->getId(), 'login');
                
                header("Location: dashboard.php");
                exit();
            } else {
                // armazena tentativas falhas
                $_SESSION['_login_attempts'][] = $now;
                // mitiga ataques de timestamp. Caso o user não exista ele não procura senha, então a resposta demoraria menos
                usleep(200000);
                return "Credenciais inválidas!";
            }
        }
        
        return null;
    }
    
    public function logout() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // limpa dados da sessão e cookie
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        session_destroy();
        header("Location: index.php");
        exit();
    }
    
    private function logUserActivity($userId, $action) {
        // novas sanitizações
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        $userAgent = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
        $userAgent = str_replace(["\r", "\n"], ['\\r', '\\n'], $userAgent);
        $timestamp = date('Y-m-d H:i:s');
        
        $logEntry = "$timestamp | User ID: $userId | Action: $action | IP: $ip | Agent: $userAgent\n";
        if (!is_dir('logs')) {
            // restringe permissões de diretório
            @mkdir('logs', 0700, true);
        }
        // usa lock pra garantir integridade dos logs em caso de dois ou mais logins simultâneos
        file_put_contents('logs/user_activity.log', $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    // não setta nada, apenas valida se a sessão é válida 
    public function validateSession() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        return true;
    }
}