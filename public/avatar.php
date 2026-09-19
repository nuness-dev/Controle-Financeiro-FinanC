<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auth;
use App\Core\Session;
use App\Repository\UserRepository;
use App\Service\UploadService;

Session::start();
Auth::requireLogin();

$usuario = (new UserRepository())->buscarPorId((int) Auth::id());

if (!$usuario || empty($usuario['foto'])) {
    http_response_code(404);
    exit;
}

$caminho = (new UploadService())->caminhoFotoPerfil($usuario['foto']);

if (!is_file($caminho)) {
    http_response_code(404);
    exit;
}

$mime = mime_content_type($caminho) ?: 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($caminho));
header('Cache-Control: private, max-age=300');
header('X-Content-Type-Options: nosniff');

readfile($caminho);
exit;
