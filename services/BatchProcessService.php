<?php
namespace App\Services;
use App\Models\User;
use App\Utils\Security;

class BatchProcessService {
    public function processUserBatch($dataArray) {
        if (!is_array($dataArray)) {
            return ["error" => "Dados inválidos"];
        }
        
        return User::bulkUpdate($dataArray);
    }
    
    public function importFromJson($jsonData) {
        $data = json_decode($jsonData, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ["error" => "JSON inválido"];
        }
        if (!is_array($data)) {
            return ["error" => "JSON inválido"];
        }
        
        $results = [];
        foreach ($data as $item) {
            if (!is_array($item)) {
                $results[] = "Item inválido";
                continue;
            }

            // normalizações/validações 
            if (isset($item['email']) && is_string($item['email']) && $item['email'] !== '' && !filter_var($item['email'], FILTER_VALIDATE_EMAIL)) {
                $results[] = "Email inválido para usuário";
                continue;
            }
            if (isset($item['password']) && is_string($item['password']) && $item['password'] !== '') {
                $item['password'] = Security::hashPassword($item['password']);
            }
            $user = new User($item);
            if ($user->save()) {
                $username = isset($item['username']) ? (string)$item['username'] : '';
                $results[] = "Usuário {$username} importado com sucesso";
            } else {
                $username = isset($item['username']) ? (string)$item['username'] : '';
                $results[] = "Falha ao importar {$username}";
            }
        }
        
        return $results;
    }
    
    public function executeCustomQuery($query) {
        // Execução de SQL arbitrário é inseguro 
        return ["error" => "Funcionalidade desabilitada por segurança"];
    }
}