<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Controller\ReportController;
use App\Core\Auth;
use App\Core\Response;
use App\Core\Session;

Session::start();
Auth::requireLogin();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    Response::error('Método não suportado.', 405);
}

$controller = new ReportController();
$formato = (string) ($_GET['formato'] ?? 'json');

if ($formato === 'csv') {
    $controller->exportarCsv();
} else {
    $controller->resumo();
}
