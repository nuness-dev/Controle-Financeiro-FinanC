<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;

Session::start();
Auth::requireLogin();

$title = 'Transações — FinanC';
$active = 'transacoes';
$pageTitle = 'Transações';
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

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
            <div class="flex flex-col lg:flex-row lg:items-center gap-3">
                <div class="relative flex-1">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="filtro-busca" placeholder="Buscar por descrição..."
                        class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <button id="btn-filtros" class="lg:hidden flex items-center justify-center gap-2 text-sm font-medium text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2">
                    <i data-lucide="sliders-horizontal" class="w-4 h-4"></i> Filtros
                </button>
                <button id="btn-nova-transacao" class="flex items-center justify-center gap-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors whitespace-nowrap">
                    <i data-lucide="plus" class="w-4 h-4"></i> Nova transação
                </button>
            </div>

            <div id="painel-filtros" class="hidden lg:grid grid-cols-2 lg:grid-cols-4 gap-2 mt-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                <select id="filtro-tipo" class="text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2">
                    <option value="">Todos os tipos</option>
                    <option value="receita">Receita</option>
                    <option value="despesa">Despesa</option>
                    <option value="transferencia">Transferência</option>
                </select>
                <select id="filtro-status" class="text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2">
                    <option value="">Todos os status</option>
                    <option value="pendente">Pendente</option>
                    <option value="pago">Pago</option>
                    <option value="cancelado">Cancelado</option>
                </select>
                <select id="filtro-categoria" class="text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2">
                    <option value="">Todas as categorias</option>
                </select>
                <select id="filtro-conta" class="text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2">
                    <option value="">Todas as contas</option>
                </select>
                <input type="date" id="filtro-data-inicio" class="text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2">
                <input type="date" id="filtro-data-fim" class="text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2">
                <input type="text" id="filtro-valor-min" inputmode="decimal" data-money-input placeholder="Valor mín." class="text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2">
                <input type="text" id="filtro-valor-max" inputmode="decimal" data-money-input placeholder="Valor máx." class="text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2">
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                <span id="transacoes-count" class="text-sm text-slate-400">Carregando…</span>
            </div>
            <div class="overflow-x-auto">
                <table class="fin-table w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-slate-400 uppercase border-b border-slate-100 dark:border-slate-800">
                            <th class="py-3 px-4 font-medium cursor-pointer select-none" data-sort="data">Data</th>
                            <th class="py-3 px-4 font-medium cursor-pointer select-none" data-sort="descricao">Descrição</th>
                            <th class="py-3 px-4 font-medium">Categoria</th>
                            <th class="py-3 px-4 font-medium">Conta</th>
                            <th class="py-3 px-4 font-medium">Status</th>
                            <th class="py-3 px-4 font-medium text-right cursor-pointer select-none" data-sort="valor">Valor</th>
                            <th class="py-3 px-4 font-medium text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="transacoes-tbody"></tbody>
                </table>
            </div>
            <div id="transacoes-pagination" class="flex items-center gap-1 px-4 py-3 border-t border-slate-100 dark:border-slate-800"></div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../views/partials/modal-transacao.php'; ?>
<?php include __DIR__ . '/../views/partials/confirm-modal.php'; ?>

<script>window.csrfToken = <?= json_encode(Csrf::token()) ?>;</script>
<script src="assets/js/api.js"></script>
<script src="assets/js/theme.js"></script>
<script src="assets/js/toast.js"></script>
<script src="assets/js/modal.js"></script>
<script src="assets/js/moneyInput.js"></script>
<script src="assets/js/transactions.js"></script>
</body>
</html>
