<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Controller\AuthController;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

Session::start();

$request = new Request();
$controller = new AuthController();
$action = (string) $request->query('action', '');

if ($request->method() !== 'POST') {
    Response::error('Método não suportado.', 405);
}

if ($action !== 'logout') {
    Csrf::requireValid((string) $request->input('csrf_token'));
}

switch ($action) {
    case 'registrar':
        $controller->registrar($request);
        break;
    case 'login':
        $controller->login($request);
        break;
    case 'logout':
        $controller->logout();
        break;
    case 'esqueci-senha':
        $controller->esqueciSenha($request);
        break;
    case 'redefinir-senha':
        $controller->redefinirSenha($request);
        break;
    default:
        Response::error('Ação inválida.', 400);
}
