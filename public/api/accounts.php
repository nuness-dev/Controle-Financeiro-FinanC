<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Controller\AccountController;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

Session::start();
Auth::requireLogin();

$request = new Request();
$controller = new AccountController();
$id = $request->query('id') !== null ? (int) $request->query('id') : null;

match ($request->method()) {
    'GET' => $id !== null ? $controller->obter($id) : $controller->listar(),
    'POST' => $controller->criar($request),
    'PUT' => $id !== null ? $controller->atualizar($request, $id) : Response::error('ID da conta é obrigatório.', 400),
    'DELETE' => $id !== null ? $controller->excluir($request, $id) : Response::error('ID da conta é obrigatório.', 400),
    default => Response::error('Método não suportado.', 405),
};
