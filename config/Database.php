<?php
namespace App\Config;

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $config = [
            'host' => 'localhost',
            'user' => 'root',
            'password' => 'P@ssw0rd123',
            'dbname' => 'app_sistema'
        ];
        
        $this->connection = new \mysqli(
            $config['host'],
            $config['user'],
            $config['password'],
            $config['dbname']
        );
        
        if ($this->connection->connect_error) {
            die("Falha na conexão: " . $this->connection->connect_error);
        }
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
        return $this->connection->query($sql);
    }
    
    public function escapeString($string) {
        return $this->connection->real_escape_string($string);
    }
}