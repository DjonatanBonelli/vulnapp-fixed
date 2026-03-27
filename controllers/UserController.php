<?php
namespace App\Controllers;
use App\Models\User;
use App\Services\FileService;

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
            
            $id = $_POST['id'];
            $nome = $_POST['nome'];
            $email = $_POST['email'];
            $bio = $_POST['bio'];
            
            $loggedInUserId = $_SESSION['user_id'] ?? 0;
            
            $user = User::findById($id);
            
            if ($user) {
                $user->setNome($nome);
                $user->setEmail($email);
                $user->setBio($bio);
                
                if ($user->save()) {
                    // Verifica se há upload de avatar
                    if (isset($_FILES['avatar'])) {
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
        $user = User::findById($userId);
        
        if (!$user) {
            echo "Usuário não encontrado";
            return;
        }
        
        echo "<form method='post' action='update_profile.php' enctype='multipart/form-data'>";
        echo "<input type='hidden' name='id' value='" . $user->getId() . "'>";
        echo "<label>Nome:</label>";
        echo "<input type='text' name='nome' value='" . $user->getNome() . "'><br>";
        echo "<label>Email:</label>";
        echo "<input type='email' name='email' value='" . $user->getEmail() . "'><br>";
        echo "<label>Bio:</label>";
        echo "<textarea name='bio'>" . $user->getBio() . "</textarea><br>";
        echo "<label>Avatar:</label>";
        echo "<input type='file' name='avatar'><br>";
        echo "<input type='submit' name='update_profile' value='Atualizar'>";
        echo "</form>";
    }
}