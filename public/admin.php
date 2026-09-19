<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;

Session::start();
Auth::requireAdmin();

$title = 'Administração — FinanC';
$active = 'admin';
$pageTitle = 'Administração';
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
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
                <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Usuários ativos</p>
                <p id="admin-usuarios-ativos" class="text-2xl font-bold text-slate-900 dark:text-white mt-1">—</p>
            </div>
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 sm:col-span-2">
                <p class="text-sm font-semibold text-slate-900 dark:text-white">Acesso administrativo</p>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Esta área gerencia apenas usuários e status da conta. Dados financeiros continuam isolados por usuário.</p>
            </div>
        </div>

        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Usuários</h2>
                <button id="admin-recarregar" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-700 dark:text-brand-300 px-3 py-2 rounded-lg hover:bg-brand-50 dark:hover:bg-brand-500/10">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i> Recarregar
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="fin-table w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-slate-400 uppercase border-b border-slate-100 dark:border-slate-800">
                            <th class="py-3 px-4 font-medium">Usuário</th>
                            <th class="py-3 px-4 font-medium">Perfil</th>
                            <th class="py-3 px-4 font-medium">Status</th>
                            <th class="py-3 px-4 font-medium">Último acesso</th>
                            <th class="py-3 px-4 font-medium text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="admin-tbody"></tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<script>
window.csrfToken = <?= json_encode(Csrf::token()) ?>;
window.usuarioAtualId = <?= json_encode(Auth::id()) ?>;
</script>
<script src="assets/js/api.js"></script>
<script src="assets/js/theme.js"></script>
<script src="assets/js/toast.js"></script>
<script src="assets/js/admin.js"></script>
</body>
</html>
