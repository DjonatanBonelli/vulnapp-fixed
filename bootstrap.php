<?php
// Auto-loading
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Não mostra erros detalhados em produção
$appEnv = getenv('APP_ENV') ?: 'production';
if ($appEnv === 'development') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
}

ini_set('session.use_strict_mode', '1');    // evita session fixation
ini_set('session.use_only_cookies', '1');   // impede session via url
ini_set('session.cookie_httponly', '1');    // impede roubo via xss
ini_set('session.cookie_samesite', 'Lax');  // ajuda a mitigar csrf

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}