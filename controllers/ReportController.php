<?php
namespace App\Controllers;

class ReportController {
    private $reportBaseDir = 'reports/';
    
    public function downloadReport($reportId) {

        $filepath = $this->reportBaseDir . $reportId;
        
        if (file_exists($filepath)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
            readfile($filepath);
            exit;
        } else {
            return "Relatório não encontrado";
        }
    }
    
    public function generateReport($data, $type) {
        
        if ($type === 'pdf') {
            $filename = 'report_' . time();
            $filePath = $this->reportBaseDir . $filename;
            
            // Gera arquivo com dados
            file_put_contents($filePath . '.json', json_encode($data));
            
            $command = "python3 scripts/generate_pdf.py {$filePath}.json {$filePath}";
            system($command);
            
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