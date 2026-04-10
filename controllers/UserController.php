<?php
namespace App\Controllers;
use App\Models\User;
use App\Services\FileService;
use App\Utils\Security;

class UserController {
    private $fileService;
    
    public function __construct() {
        $this->fileService = new FileService();
    }
    
    public function getProfile($userId) {
        
        $user = User::findById($userId);
        
        if ($user) {
            return [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'nome' => $user->getNome(),
                'bio' => $user->getBio(),
                'role' => $user->getRole()
            ];
        }
        
        return null;
    }
    
    public function updateProfile() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
            // requer sessão
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            if (!Security::verifyCsrfToken($_POST['_csrf'] ?? null)) {
                return "Requisição inválida.";
            }
            
            // sanitiza inputs
            $id = (int)($_POST['id'] ?? 0);
            $nome = Security::sanitizeInput($_POST['nome'] ?? '');
            $email = Security::sanitizeInput($_POST['email'] ?? '');
            $bio = Security::sanitizeInput($_POST['bio'] ?? '');
            
            $loggedInUserId = (int)($_SESSION['user_id'] ?? 0);
            $loggedInRole = (string)($_SESSION['role'] ?? '');

            if ($loggedInUserId <= 0) {
                return "Não autenticado.";
            }

            // prevenção de IDOR: o usuário só pode atualizar o próprio perfil, a menos que seja administrador.
            if ($id !== $loggedInUserId && $loggedInRole !== 'admin') {
                return "Acesso negado.";
            }

            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return "Email inválido.";
            }

            // limites de tamanho para evitar abusos
            $nome = mb_substr($nome, 0, 120);
            $email = mb_substr($email, 0, 254);
            $bio = mb_substr($bio, 0, 2000);
            
            $user = User::findById($id);
            
            if ($user) {
                $user->setNome($nome);
                $user->setEmail($email);
                $user->setBio($bio);
                
                if ($user->save()) {
                    // Verifica se há upload de avatar
                    if (isset($_FILES['avatar']) && is_array($_FILES['avatar'])) {
                        $avatarPath = $this->fileService->uploadAvatar($_FILES['avatar']);
                        
                        if ($avatarPath) {
                            // Atualiza o caminho do avatar no banco
                            // ...
                            // Implementação omitida para simplificar
                            return "Perfil atualizado com sucesso incluindo avatar!";
                        }
                    }
                    
                    return "Perfil atualizado com sucesso!";
                } else {
                    return "Erro ao atualizar perfil";
                }
            }
            
            return "Usuário não encontrado";
        }
        
        return null;
    }
    
    public function renderProfileForm($userId) {
        // exige sessão para token csrf persistir, IDOR é tratado na função acima (que supostamente é chamada ao submeter esse form)
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $user = User::findById($userId);
        
        if (!$user) {
            echo "Usuário não encontrado";
            return;
        }
        // sanitizações aplicadas para mitigar xss e envio de token para impedir csrf
        echo "<form method='post' action='update_profile.php' enctype='multipart/form-data'>";
        echo "<input type='hidden' name='_csrf' value='" . Security::e(Security::csrfToken()) . "'>";
        echo "<input type='hidden' name='id' value='" . Security::e((string)$user->getId()) . "'>";
        echo "<label>Nome:</label>";
        echo "<input type='text' name='nome' value='" . Security::e($user->getNome()) . "'><br>";
        echo "<label>Email:</label>";
        echo "<input type='email' name='email' value='" . Security::e($user->getEmail()) . "'><br>";
        echo "<label>Bio:</label>";
        echo "<textarea name='bio'>" . Security::e($user->getBio()) . "</textarea><br>";
        echo "<label>Avatar:</label>";
        echo "<input type='file' name='avatar'><br>";
        echo "<input type='submit' name='update_profile' value='Atualizar'>";
        echo "</form>";
    }
}