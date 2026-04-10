<?php
namespace App\Services;

class FileService {
    private $uploadDir = 'uploads/avatars/';
    private $thumbDir = 'uploads/thumbnails/';
    private $maxUploadBytes = 2097152; // 2MB
    private $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    private function ensureDir(string $dir): void {
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
    }

    private function safeJoinUnder(string $baseDir, string $path): ?string {
        $baseReal = realpath($baseDir);
        if ($baseReal === false) return null;

        $candidate = $path;
        // se um path relativo for passado, ancora ao diretório base
        if (!str_starts_with($candidate, $baseDir) && !str_starts_with($candidate, $baseReal)) {
            $candidate = rtrim($baseDir, '/\\') . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
        }
        $candReal = realpath($candidate);
        if ($candReal === false) return null;
        if (strpos($candReal, $baseReal . DIRECTORY_SEPARATOR) !== 0 && $candReal !== $baseReal) {
            return null;
        }
        return $candReal;
    }
    
    public function uploadAvatar($fileData) {
        $this->ensureDir($this->uploadDir);

        if (!isset($fileData['error']) || $fileData['error'] !== UPLOAD_ERR_OK) {   // garante que o arquivo chegou inteiro
            return false;
        }
        if (!isset($fileData['tmp_name']) || !is_uploaded_file($fileData['tmp_name'])) {    // restringe local file inclusion
            return false;
        }
        $size = (int)($fileData['size'] ?? 0);
        if ($size <= 0 || $size > $this->maxUploadBytes) {  // controle de tamanho do arquivo
            return false;
        }

        $origName = (string)($fileData['name'] ?? '');
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION)); 
        if (!in_array($ext, $this->allowedExt, true)) {     // verifica extensão
            return false;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);    // verifica magic bytes
        $mime = $finfo->file($fileData['tmp_name']);
        if (!is_string($mime) || !in_array($mime, $this->allowedMime, true)) {
            return false;
        }

        // safe path
        $safeName = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetFile = rtrim($this->uploadDir, '/\\') . DIRECTORY_SEPARATOR . $safeName;

        if (move_uploaded_file($fileData['tmp_name'], $targetFile)) {
            // restringe permissões
            @chmod($targetFile, 0600);
            return $targetFile;
        }

        return false;
    }
    
    public function getImageContent($imagePath) {
        // verificação se está no safe base dir, medida contra path transversal
        $real = $this->safeJoinUnder($this->uploadDir, (string)$imagePath);
        if ($real === null || !is_file($real)) {
            return null;
        }
        return file_get_contents($real);
    }
    
    public function createThumbnail($imagePath, $width, $height) {
        $this->ensureDir($this->thumbDir);
        // impede image bombing
        $width = max(1, min(1024, (int)$width));
        $height = max(1, min(1024, (int)$height));

        $real = $this->safeJoinUnder($this->uploadDir, (string)$imagePath);
        if ($real === null || !is_file($real)) {
            return false;
        }
        
        $info = getimagesize($real);
        
        if ($info === false) {
            return false;
        }

        // medidas anti-RCE
        [$srcW, $srcH, $type] = $info;
        // limpeza de metadados
        switch ($type) {
            case IMAGETYPE_JPEG:
                $src = imagecreatefromjpeg($real);
                $outExt = 'jpg';
                break;
            case IMAGETYPE_PNG:
                $src = imagecreatefrompng($real);
                $outExt = 'png';
                break;
            case IMAGETYPE_GIF:
                $src = imagecreatefromgif($real);
                $outExt = 'gif';
                break;
            case IMAGETYPE_WEBP:
                if (!function_exists('imagecreatefromwebp')) return false;
                $src = imagecreatefromwebp($real);
                $outExt = 'webp';
                break;
            default:
                return false;
        }

        if (!$src) return false;
        // defesa contra arquivos poliglotas
        $thumb = imagecreatetruecolor($width, $height);
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF || $type === IMAGETYPE_WEBP) {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
            imagefilledrectangle($thumb, 0, 0, $width, $height, $transparent);
        }

        imagecopyresampled($thumb, $src, 0, 0, 0, 0, $width, $height, $srcW, $srcH);
        
        // normalização de extensão real
        $thumbName = bin2hex(random_bytes(16)) . '.' . $outExt;
        $thumbnailPath = rtrim($this->thumbDir, '/\\') . DIRECTORY_SEPARATOR . $thumbName;
        $ok = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $ok = imagejpeg($thumb, $thumbnailPath, 85);
                break;
            case IMAGETYPE_PNG:
                $ok = imagepng($thumb, $thumbnailPath);
                break;
            case IMAGETYPE_GIF:
                $ok = imagegif($thumb, $thumbnailPath);
                break;
            case IMAGETYPE_WEBP:
                $ok = imagewebp($thumb, $thumbnailPath, 85);
                break;
        }

        imagedestroy($src);
        imagedestroy($thumb);

        if ($ok) {
            @chmod($thumbnailPath, 0600);
            return $thumbnailPath;
        }
        return false;
    }
}