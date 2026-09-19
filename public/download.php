<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auth;
use App\Core\Session;
use App\Repository\AttachmentRepository;
use App\Service\UploadService;

Session::start();
Auth::requireLogin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$anexo = (new AttachmentRepository())->buscarPorIdEUsuario($id, Auth::id());

if ($anexo === null) {
    http_response_code(404);
    exit('Anexo não encontrado.');
}

$caminho = (new UploadService())->caminhoAnexo($anexo['nome_arquivo']);

if (!is_file($caminho)) {
    http_response_code(404);
    exit('Arquivo não encontrado.');
}

header('Content-Type: ' . $anexo['mime']);
header('Content-Length: ' . (string) filesize($caminho));
header('Content-Disposition: inline; filename="' . rawurlencode($anexo['nome_original']) . '"');
header('X-Content-Type-Options: nosniff');

readfile($caminho);
exit;
