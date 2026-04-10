<?php
namespace App\Config;

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        // variáveis em .env
        $config = [
            'host' => getenv('DB_HOST') ?: 'localhost',
            'user' => getenv('DB_USER') ?: 'root',
            'password' => getenv('DB_PASS') ?: '',
            'dbname' => getenv('DB_NAME') ?: 'app_sistema'
        ];
        
        $this->connection = new \mysqli(
            $config['host'],
            $config['user'],
            $config['password'],
            $config['dbname']
        );
        
        if ($this->connection->connect_error) {
            // evita vazar erros detalhados
            throw new \RuntimeException("Falha na conexão com o banco de dados.");
        }

        // evita escapes de caracteres 
        $this->connection->set_charset('utf8mb4');
    }
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function executeQuery($sql) {
        // função desabilitada por execução insegura de sql 
        throw new \RuntimeException("Execução de SQL cru desabilitada. Utilize a nova função: prepare()");
    }

    // nova função com queries stmt
    public function prepare(string $sql): \mysqli_stmt {
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new \RuntimeException("Falha ao preparar query.");
        }
        return $stmt;
    }
    
    public function escapeString($string) {
        return $this->connection->real_escape_string($string);
    }
}