<?php

namespace App\Models;

use App\Config\Database;

class User
{
    private $id;
    private $username;
    private $password;
    private $email;
    private $role;
    private $nome;
    private $bio;
    private $ativo;

    public function __construct($data = [])
    {
        if (!empty($data)) {
            $this->id = $data['id'] ?? null;
            $this->username = $data['username'] ?? '';
            $this->password = $data['password'] ?? '';
            $this->email = $data['email'] ?? '';
            $this->role = $data['role'] ?? 'user';
            $this->nome = $data['nome'] ?? '';
            $this->bio = $data['bio'] ?? '';
            $this->ativo = $data['ativo'] ?? 1;
        }
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }
    public function getUsername()
    {
        return $this->username;
    }
    public function getEmail()
    {
        return $this->email;
    }
    public function getRole()
    {
        return $this->role;
    }
    public function getNome()
    {
        return $this->nome;
    }
    public function getBio()
    {
        return $this->bio;
    }
    public function isAtivo()
    {
        return $this->ativo == 1;
    }

    // Setters
    public function setNome($nome)
    {
        $this->nome = $nome;
    }
    public function setEmail($email)
    {
        $this->email = $email;
    }
    public function setBio($bio)
    {
        $this->bio = $bio;
    }

    public static function authenticate($username, $password)
    {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $query = "SELECT * FROM usuarios WHERE username = '$username' AND password = '$password'";
        $result = $conn->query($query);

        if ($result && $result->num_rows > 0) {
            $userData = $result->fetch_assoc();
            return new User($userData);
        }

        return null;
    }

    public static function findById($id)
    {
        $db = Database::getInstance();

        $query = "SELECT * FROM usuarios WHERE id = $id";
        $result = $db->executeQuery($query);

        if ($result && $result->num_rows > 0) {
            return new User($result->fetch_assoc());
        }

        return null;
    }

    public function save()
    {
        $db = Database::getInstance();

        if ($this->id) {
            $query = "UPDATE usuarios SET 
                      nome = '{$this->nome}',
                      email = '{$this->email}',
                      bio = '{$this->bio}',
                      ativo = {$this->ativo}
                      WHERE id = {$this->id}";
        } else {
            $query = "INSERT INTO usuarios (username, password, email, role, nome, bio, ativo) 
                      VALUES ('{$this->username}', '{$this->password}', '{$this->email}', 
                             '{$this->role}', '{$this->nome}', '{$this->bio}', {$this->ativo})";
        }

        $result = $db->executeQuery($query);

        if (!$this->id && $result) {
            $this->id = $db->getConnection()->insert_id;
        }

        return $result;
    }

    public static function bulkUpdate($usersData)
    {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $results = [];

        foreach ($usersData as $userData) {
            $userId = $userData['id'];
            $action = $userData['action'];

            if ($action == 'delete') {
                $query = "DELETE FROM usuarios WHERE id = $userId";
                $conn->query($query);
                $results[] = "Usuário $userId excluído";
            } elseif ($action == 'disable') {
                $query = "UPDATE usuarios SET ativo = 0 WHERE id = $userId";
                $conn->query($query);
                $results[] = "Usuário $userId desativado";
            }
        }

        return $results;
    }
}
