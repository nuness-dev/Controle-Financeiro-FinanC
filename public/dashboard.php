<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auth;
use App\Core\Session;

Session::start();
Auth::requireLogin();

$title = 'Dashboard — FinanC';
$active = 'dashboard';
$pageTitle = 'Dashboard';
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

        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <select id="filtro-periodo" class="text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
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
                <button id="periodo-aplicar" class="text-sm font-medium text-brand-600 dark:text-brand-400 px-3 py-2">Aplicar</button>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Saldo atual</p>
                <p id="card-saldo-atual" class="text-xl font-bold text-slate-900 dark:text-white mt-1">—</p>
            </div>
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Saldo previsto</p>
                <p id="card-saldo-previsto" class="text-xl font-bold text-slate-900 dark:text-white mt-1">—</p>
            </div>
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Receitas</p>
                <p id="card-receitas" class="text-xl font-bold text-emerald-600 mt-1">—</p>
            </div>
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Despesas</p>
                <p id="card-despesas" class="text-xl font-bold text-rose-600 mt-1">—</p>
            </div>
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Contas pendentes</p>
                <p id="card-pendentes" class="text-xl font-bold text-amber-600 mt-1">—</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-3">Receitas x despesas</p>
                <div class="h-56"><canvas id="grafico-receita-despesa"></canvas></div>
            </div>
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-3">Despesas por categoria</p>
                <div class="h-56"><canvas id="grafico-categoria"></canvas></div>
            </div>
            <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-3">Evolução do período</p>
                <div class="h-56"><canvas id="grafico-evolucao"></canvas></div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-2">Últimas transações</p>
                <div id="lista-ultimas-transacoes"></div>
            </div>
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-2">Próximos vencimentos</p>
                <div id="lista-vencimentos"></div>
            </div>
        </div>
    </main>
</div>

<script src="assets/js/api.js"></script>
<script src="assets/js/theme.js"></script>
<script src="assets/js/toast.js"></script>
<script src="assets/js/dashboard.js"></script>
</body>
</html>
