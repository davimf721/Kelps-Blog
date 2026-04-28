<?php

declare(strict_types=1);

namespace App\Service;

use RuntimeException;

class UploadService
{
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const MAX_SIZE      = 5 * 1024 * 1024; // 5 MB

    public function __construct(private string $uploadDir) {}

    /** Processa o upload de uma imagem e retorna o path relativo salvo. */
    public function handleImage(array $file, string $subdir = ''): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Erro no upload do arquivo.');
        }

        if ($file['size'] > self::MAX_SIZE) {
            throw new RuntimeException('Arquivo muito grande. Limite: 5 MB.');
        }

        $mime = mime_content_type($file['tmp_name']);

        if (! in_array($mime, self::ALLOWED_TYPES, true)) {
            throw new RuntimeException('Tipo de arquivo não permitido. Use JPEG, PNG, GIF ou WebP.');
        }

        $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
        $name = bin2hex(random_bytes(16)) . '.' . strtolower($ext);
        $dir  = rtrim($this->uploadDir . '/' . $subdir, '/');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $dest = $dir . '/' . $name;

        if (! move_uploaded_file($file['tmp_name'], $dest)) {
            throw new RuntimeException('Não foi possível salvar o arquivo.');
        }

        return ($subdir ? $subdir . '/' : '') . $name;
    }

    public function delete(string $relativePath): void
    {
        $full = $this->uploadDir . '/' . ltrim($relativePath, '/');

        if (is_file($full)) {
            unlink($full);
        }
    }
}
