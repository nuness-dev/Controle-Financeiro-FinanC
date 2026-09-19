<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Controller\UserController;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

Session::start();
Auth::requireLogin();

$request = new Request();
$controller = new UserController();
$action = (string) $request->query('action', '');
$id = $request->query('id') !== null ? (int) $request->query('id') : null;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($action === 'admin-listar') {
    Auth::requireAdmin();
    $controller->listarAdmin();
} elseif ($action === 'admin-status' && $id !== null) {
    Auth::requireAdmin();
    $controller->alterarStatus($request, $id);
} elseif ($action === 'admin-perfil' && $id !== null) {
    Auth::requireAdmin();
    $controller->alterarPerfil($request, $id);
} elseif ($action === 'foto' && $method === 'POST') {
    $controller->uploadFoto();
} elseif ($action === 'remover-foto' && $method === 'POST') {
    $controller->removerFoto($request);
} elseif ($action === 'senha' && $method === 'POST') {
    $controller->atualizarSenha($request);
} elseif ($method === 'POST') {
    $controller->atualizarPerfil($request);
} else {
    Response::error('Requisição inválida.', 400);
}
