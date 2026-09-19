<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Controller\TransactionController;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

Session::start();
Auth::requireLogin();

$request = new Request();
$controller = new TransactionController();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    $transactionId = (int) ($_POST['transaction_id'] ?? 0);
    if ($transactionId <= 0) {
        Response::error('ID da transação é obrigatório.', 400);
    }
    $controller->upload($transactionId);
} elseif ($method === 'DELETE') {
    $anexoId = $request->query('id') !== null ? (int) $request->query('id') : null;
    if ($anexoId === null) {
        Response::error('ID do anexo é obrigatório.', 400);
    }
    $controller->excluirAnexo($request, $anexoId);
} else {
    Response::error('Método não suportado.', 405);
}
