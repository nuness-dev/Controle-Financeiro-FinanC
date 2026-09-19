<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Controller\InvoiceController;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

Session::start();
Auth::requireLogin();

$request = new Request();
$controller = new InvoiceController();
$id = $request->query('id') !== null ? (int) $request->query('id') : null;
$action = (string) $request->query('action', '');

if ($request->method() === 'GET') {
    if ($id !== null) {
        $controller->obter($id);
    } else {
        $controller->listarPorCartao($request);
    }
} elseif ($request->method() === 'POST' && $action === 'compra') {
    $controller->registrarCompra($request);
} elseif ($request->method() === 'POST' && $action === 'pagar' && $id !== null) {
    $controller->pagar($request, $id);
} else {
    Response::error('Requisição inválida.', 400);
}
