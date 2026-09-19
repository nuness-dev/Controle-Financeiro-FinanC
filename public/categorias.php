<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;

Session::start();
Auth::requireLogin();

$title = 'Categorias — FinanC';
$active = 'categorias';
$pageTitle = 'Categorias';
$userNome = Auth::nome();
$userFoto = Auth::foto();
$isAdmin = Auth::isAdmin();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<?php include __DIR__ . '/../views/partials/head.php'; ?>
</head>
<body class="h-screen flex bg-slate-50 dark:bg-slate-950">

<?php include __DIR__ . '/../views/partials/sidebar.php'; ?>

<div class="flex-1 flex flex-col min-w-0">
<?php include __DIR__ . '/../views/partials/navbar.php'; ?>
<?php include __DIR__ . '/../views/partials/toast.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 lg:p-6 space-y-4">
        <div class="flex items-center justify-between">
            <p class="text-sm text-slate-500 dark:text-slate-400">Organize receitas e despesas por categoria.</p>
            <button id="btn-nova-categoria" class="flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
                <i data-lucide="plus" class="w-4 h-4"></i> Nova categoria
            </button>
        </div>

        <div>
            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-2">Receitas</p>
            <div id="categorias-receita" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-6"></div>
            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-2">Despesas</p>
            <div id="categorias-despesa" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3"></div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../views/partials/modal-categoria.php'; ?>
<?php include __DIR__ . '/../views/partials/confirm-modal.php'; ?>

<script>window.csrfToken = <?= json_encode(Csrf::token()) ?>;</script>
<script src="assets/js/api.js"></script>
<script src="assets/js/theme.js"></script>
<script src="assets/js/toast.js"></script>
<script src="assets/js/modal.js"></script>
<script src="assets/js/categories.js"></script>
</body>
</html>
