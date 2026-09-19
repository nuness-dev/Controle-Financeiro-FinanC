<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auth;
use App\Core\Session;

Session::start();
Auth::requireLogin();

$title = 'Relatórios — FinanC';
$active = 'relatorios';
$pageTitle = 'Relatórios';
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

    <main class="flex-1 overflow-y-auto p-4 lg:p-6 space-y-6">

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center gap-3">
            <select id="filtro-periodo" class="text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2">
                <option value="este_mes">Este mês</option>
                <option value="mes_anterior">Mês anterior</option>
                <option value="ultimos_30">Últimos 30 dias</option>
                <option value="ultimos_90">Últimos 90 dias</option>
                <option value="personalizado">Personalizado</option>
            </select>
            <div id="periodo-customizado" class="hidden flex items-center gap-2">
                <input type="date" id="periodo-inicio" class="text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2">
                <span class="text-slate-400 text-sm">até</span>
                <input type="date" id="periodo-fim" class="text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2">
            </div>
            <button id="periodo-aplicar" class="text-sm font-medium text-brand-600 dark:text-brand-400 px-3 py-2">Aplicar</button>
            <a id="btn-exportar-csv" href="#" class="ml-auto flex items-center gap-2 text-sm font-semibold text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-700 rounded-lg px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800">
                <i data-lucide="download" class="w-4 h-4"></i> Exportar CSV
            </a>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Receitas</p>
                <p id="rel-receitas" class="text-xl font-bold text-emerald-600 mt-1">—</p>
            </div>
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Despesas</p>
                <p id="rel-despesas" class="text-xl font-bold text-rose-600 mt-1">—</p>
            </div>
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Resultado do período</p>
                <p id="rel-resultado" class="text-xl font-bold text-slate-900 dark:text-white mt-1">—</p>
            </div>
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Saldo previsto</p>
                <p id="rel-saldo-previsto" class="text-xl font-bold text-slate-900 dark:text-white mt-1">—</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-3">Gastos por categoria</p>
                <div class="h-64"><canvas id="rel-grafico-categoria"></canvas></div>
            </div>
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-3">Fluxo de caixa</p>
                <div class="h-64"><canvas id="rel-grafico-fluxo"></canvas></div>
            </div>
        </div>
    </main>
</div>

<script src="assets/js/api.js"></script>
<script src="assets/js/theme.js"></script>
<script src="assets/js/toast.js"></script>
<script src="assets/js/reports.js"></script>
</body>
</html>
