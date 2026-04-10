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

    private static function isPasswordHash(string $stored): bool
    {
        // agora o hash é nativo do php. Utilizará um desses algoritmos
        return str_starts_with($stored, '$2y$')
            || str_starts_with($stored, '$argon2id$')
            || str_starts_with($stored, '$argon2i$');
    }

    public static function authenticate($username, $password)
    {
        // stmt
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $userData = $result->fetch_assoc();

            $stored = (string)($userData['password'] ?? '');

            // mantém suporte para texto/MD5 legado e faz migração para hash de senha após login bem-sucedido.
            $ok = false;
            if (self::isPasswordHash($stored)) {
                $ok = password_verify($password, $stored);
            } else {
                $ok = hash_equals($stored, $password) || hash_equals($stored, md5($password));
            }

            if ($ok) {
                if (!self::isPasswordHash($stored)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    if ($newHash) {
                        $upd = $db->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
                        $upd->bind_param('si', $newHash, $userData['id']);
                        $upd->execute();
                    }
                }
                return new User($userData);
            }
        }

        return null;
    }

    public static function findById($id)
    {
        // stmt e sanitização
        $db = Database::getInstance();
        $id = (int)$id;
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            return new User($result->fetch_assoc());
        }

        return null;
    }

    public function save()
    {
        $db = Database::getInstance();

        // sanitizações e stmt
        if ($this->id) {
            $id = (int)$this->id;
            $ativo = (int)$this->ativo;
            $stmt = $db->prepare(
                "UPDATE usuarios SET nome = ?, email = ?, bio = ?, ativo = ? WHERE id = ?"
            );
            $stmt->bind_param('sssii', $this->nome, $this->email, $this->bio, $ativo, $id);
            return $stmt->execute();
        } else {
            $ativo = (int)$this->ativo;
            $password = $this->password;
            if (!self::isPasswordHash((string)$password)) {
                $password = password_hash((string)$password, PASSWORD_DEFAULT);
            }

            $stmt = $db->prepare(
                "INSERT INTO usuarios (username, password, email, role, nome, bio, ativo) VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('ssssssi', $this->username, $password, $this->email, $this->role, $this->nome, $this->bio, $ativo);
            $ok = $stmt->execute();
            if ($ok) {
                $this->id = $db->getConnection()->insert_id;
            }
            return $ok;
        }
    }

    public static function bulkUpdate($usersData)
    {
        $db = Database::getInstance();
        $results = [];

        foreach ($usersData as $userData) {
            // sanitizações e validações
            $userId = (int)($userData['id'] ?? 0);
            $action = (string)($userData['action'] ?? '');

            if ($userId <= 0) {
                $results[] = "Usuário inválido";
                continue;
            }

            if ($action === 'delete') {
                $stmt = $db->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $results[] = "Usuário $userId excluído";
            } elseif ($action === 'disable') {
                $stmt = $db->prepare("UPDATE usuarios SET ativo = 0 WHERE id = ?");
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $results[] = "Usuário $userId desativado";
            } else {
                $results[] = "Ação inválida para usuário $userId";
            }
        }

        return $results;
    }
}
