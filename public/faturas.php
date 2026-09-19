<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;

Session::start();
Auth::requireLogin();

$cardId = (int) ($_GET['card_id'] ?? 0);
if ($cardId <= 0) {
    header('Location: cartoes.php');
    exit;
}

$title = 'Faturas — FinanC';
$active = 'cartoes';
$pageTitle = 'Faturas do cartão';
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
            <a href="cartoes.php" class="flex items-center gap-1 text-sm text-slate-500 dark:text-slate-400 hover:text-brand-600"><i data-lucide="arrow-left" class="w-4 h-4"></i> Voltar aos cartões</a>
            <button id="btn-nova-compra" class="flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
                <i data-lucide="plus" class="w-4 h-4"></i> Nova compra
            </button>
        </div>

        <div id="faturas-lista" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
    </main>
</div>

<div id="compra-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/50" data-modal-dismiss></div>
    <div class="absolute inset-0 flex items-start sm:items-center justify-center p-4 overflow-y-auto">
        <form id="compra-form" class="relative bg-white dark:bg-slate-900 rounded-xl shadow-xl w-full max-w-md my-8">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Nova compra no cartão</h2>
                <button type="button" data-modal-dismiss class="text-slate-400 hover:text-slate-600" aria-label="Fechar"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="px-5 py-4 space-y-4">
                <div>
                    <label for="compra-descricao" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Descrição *</label>
                    <input type="text" name="descricao" id="compra-descricao" required maxlength="180" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="compra-valor" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Valor total *</label>
                        <input type="text" name="valor_total" id="compra-valor" required inputmode="decimal" data-money-input placeholder="0,00" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label for="compra-data" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Data *</label>
                        <input type="date" name="data_compra" id="compra-data" required class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>
                <div>
                    <label for="compra-parcelas" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Parcelas</label>
                    <input type="number" name="parcelas_total" id="compra-parcelas" min="1" max="24" value="1" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label for="compra-categoria" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Categoria</label>
                    <select name="categoria_id" id="compra-categoria" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="">Sem categoria</option>
                    </select>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-slate-200 dark:border-slate-800">
                <button type="button" data-modal-dismiss class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg">Cancelar</button>
                <button type="submit" id="compra-submit" class="px-4 py-2 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-lg">Registrar</button>
            </div>
        </form>
    </div>
</div>

<div id="pagar-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/50" data-modal-dismiss></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <form id="pagar-form" class="relative bg-white dark:bg-slate-900 rounded-xl shadow-xl w-full max-w-sm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
            <input type="hidden" id="pagar-fatura-id">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Pagar fatura</h2>
                <button type="button" data-modal-dismiss class="text-slate-400 hover:text-slate-600" aria-label="Fechar"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="px-5 py-4">
                <label for="pagar-conta" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Pagar com a conta</label>
                <select name="account_id" id="pagar-conta" required class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500"></select>
            </div>
            <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-slate-200 dark:border-slate-800">
                <button type="button" data-modal-dismiss class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg">Cancelar</button>
                <button type="submit" id="pagar-submit" class="px-4 py-2 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-lg">Confirmar pagamento</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../views/partials/confirm-modal.php'; ?>

<script>window.csrfToken = <?= json_encode(Csrf::token()) ?>; window.cardId = <?= (int) $cardId ?>;</script>
<script src="assets/js/api.js"></script>
<script src="assets/js/theme.js"></script>
<script src="assets/js/toast.js"></script>
<script src="assets/js/modal.js"></script>
<script src="assets/js/moneyInput.js"></script>
<script src="assets/js/invoices.js"></script>
</body>
</html>
