<?php
namespace App\Services;

class FileService {
    private $uploadDir = 'uploads/avatars/';
    
    public function uploadAvatar($fileData) {
        $targetFile = $this->uploadDir . basename($fileData['name']);
        
        if (move_uploaded_file($fileData['tmp_name'], $targetFile)) {
            return $targetFile;
        }
        
        return false;
    }
    
    public function getImageContent($imagePath) {
        if (file_exists($imagePath)) {
            return file_get_contents($imagePath);
        }
        
        return null;
    }
    
    public function createThumbnail($imagePath, $width, $height) {
        if (!file_exists($imagePath)) {
            return false;
        }
        
        $info = getimagesize($imagePath);
        
        if ($info === false) {
            return false;
        }
        
        // Cria thumbnail
        $thumbnailPath = 'uploads/thumbnails/' . basename($imagePath);
        
        $command = "convert {$imagePath} -resize {$width}x{$height} {$thumbnailPath}";
        exec($command);
        
        return $thumbnailPath;
    }
}