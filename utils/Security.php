<?php
namespace App\Utils;

class Security {
    public static function hashPassword($password) {
        // usa algoritmos de hash securos nativos como bcrypt 
        return password_hash((string)$password, PASSWORD_DEFAULT);
    }

    public static function verifyPassword(string $password, string $hash): bool {
        // verificação nativa 
        return password_verify($password, $hash);
    }

    public static function e(?string $value): string {
        // escapes pra mitigar xss
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
        // sanitizações adicionais
        $input = (string)$input;
        $input = str_replace(["\0"], '', $input);
        return trim($input);
    }

    // novo token csrf
    public static function csrfToken(): string {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function verifyCsrfToken(?string $token): bool {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $expected = $_SESSION['_csrf'] ?? '';
        if (!is_string($token) || $token === '' || !is_string($expected) || $expected === '') {
            return false;
        }
        return hash_equals($expected, $token);
    }
    
    public static function logDebugInfo() {
        // desabilitado em produção. 
        // se necessário para o desenvolvimento, restrinja o acesso através de APP_ENV=development e uma lista de permissões no servidor.
        return;
    }
}