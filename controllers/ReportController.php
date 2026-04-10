<?php
namespace App\Controllers;

class ReportController {
    private $reportBaseDir = 'reports/';
    
    public function downloadReport($reportId) {
        // validações de path de arquivo
        $reportId = (string)$reportId;
        // permite apenas nomes como "report_123.pdf"
        if (!preg_match('/^[a-zA-Z0-9_.-]+\.pdf$/', $reportId)) {
            return "Relatório não encontrado";
        }

        $base = realpath($this->reportBaseDir);
        if ($base === false) {
            return "Relatório não encontrado";
        }

        $filepath = $base . DIRECTORY_SEPARATOR . $reportId;
        $real = realpath($filepath);
        if ($real === false || strpos($real, $base . DIRECTORY_SEPARATOR) !== 0) {
            return "Relatório não encontrado";
        }
        
        if (is_file($real)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . basename($real) . '"');
            readfile($real);
            exit;
        } else {
            return "Relatório não encontrado";
        }
    }
    
    public function generateReport($data, $type) {
        
        if ($type === 'pdf') {
            if (!is_dir($this->reportBaseDir)) {
                @mkdir($this->reportBaseDir, 0700, true);
            }
            $filename = 'report_' . time();
            $filePath = $this->reportBaseDir . $filename;
            
            // Gera arquivo com dados
            file_put_contents($filePath . '.json', json_encode($data));
            // deve-se evitar executar comandos do shell com dados influenciados pelo usuário.
            // a geração de PDF deve ser implementada usando alguma biblioteca segura (no servidor) em vez de system()/exec().
            
            return $filename;
        }
        
        return null;
    }
    
    public function listReports() {
        $reports = [];
        
        foreach (glob($this->reportBaseDir . "*.pdf") as $file) {
            $reports[] = [
                'filename' => basename($file),
                'path' => $file,
                'size' => filesize($file),
                'created' => date("Y-m-d H:i:s", filectime($file))
            ];
        }
        
        return $reports;
    }
}