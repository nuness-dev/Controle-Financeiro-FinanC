<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Controller\GoalController;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

Session::start();
Auth::requireLogin();

$request = new Request();
$controller = new GoalController();
$id = $request->query('id') !== null ? (int) $request->query('id') : null;
$action = (string) $request->query('action', '');

if ($request->method() === 'GET') {
    $id !== null ? $controller->obter($id) : $controller->listar();
} elseif ($request->method() === 'POST' && $action === 'contribuir' && $id !== null) {
    $controller->contribuir($request, $id);
} elseif ($request->method() === 'POST') {
    $controller->criar($request);
} elseif ($request->method() === 'PUT' && $id !== null) {
    $controller->atualizar($request, $id);
} elseif ($request->method() === 'DELETE' && $id !== null) {
    $controller->excluir($request, $id);
} else {
    Response::error('Requisição inválida.', 400);
}
