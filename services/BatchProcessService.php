<?php
namespace App\Services;
use App\Models\User;

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
        
        $results = [];
        foreach ($data as $item) {
            $user = new User($item);
            if ($user->save()) {
                $results[] = "Usuário {$item['username']} importado com sucesso";
            } else {
                $results[] = "Falha ao importar {$item['username']}";
            }
        }
        
        return $results;
    }
    
    public function executeCustomQuery($query) {
        $db = \App\Config\Database::getInstance();
        $result = $db->executeQuery($query);
        
        $output = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
        }
        
        return $output;
    }
}