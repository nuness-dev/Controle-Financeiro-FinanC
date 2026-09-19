<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;
use App\Repository\UserRepository;

Session::start();
Auth::requireLogin();

$usuario = (new UserRepository())->buscarPorId((int) Auth::id());
$title = 'Perfil — FinanC';
$active = 'perfil';
$pageTitle = 'Perfil';
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

    <main class="flex-1 overflow-y-auto p-4 lg:p-6 space-y-4 max-w-5xl w-full">
        <div class="grid grid-cols-1 lg:grid-cols-[280px,1fr] gap-4">
            <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5">
                <div class="flex flex-col items-center text-center gap-3">
                    <?php if (!empty($userFoto)): ?>
                        <img id="perfil-foto-preview" src="avatar.php" alt="" class="w-24 h-24 rounded-full object-cover border border-slate-200 dark:border-slate-800">
                    <?php else: ?>
                        <div id="perfil-foto-placeholder" class="w-24 h-24 rounded-full bg-brand-600 text-white flex items-center justify-center text-3xl font-bold">
                            <?= htmlspecialchars(mb_strtoupper(mb_substr($userNome ?: '?', 0, 1))) ?>
                        </div>
                        <img id="perfil-foto-preview" src="" alt="" class="hidden w-24 h-24 rounded-full object-cover border border-slate-200 dark:border-slate-800">
                    <?php endif; ?>

                    <div>
                        <p class="text-base font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($usuario['nome'] ?? '') ?></p>
                        <p class="text-sm text-slate-500 dark:text-slate-400"><?= htmlspecialchars($usuario['email'] ?? '') ?></p>
                    </div>

                    <form id="form-foto" class="w-full space-y-3" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                        <label class="block">
                            <span class="sr-only">Foto de perfil</span>
                            <input type="file" name="foto" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-slate-500 dark:text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                        </label>
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold px-3 py-2 rounded-lg transition-colors">
                                <i data-lucide="upload" class="w-4 h-4"></i> Enviar
                            </button>
                            <button type="button" id="btn-remover-foto" class="inline-flex items-center justify-center gap-2 border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-sm font-semibold px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="space-y-4">
                <form id="form-perfil" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Dados da conta</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Atualize o nome e o email usados no sistema.</p>
                    </div>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Nome</span>
                            <input name="nome" required value="<?= htmlspecialchars($usuario['nome'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                        </label>
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Email</span>
                            <input type="email" name="email" required value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                        </label>
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="save" class="w-4 h-4"></i> Salvar perfil
                    </button>
                </form>

                <form id="form-senha" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Alterar senha</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Use pelo menos 8 caracteres.</p>
                    </div>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Senha atual</span>
                            <input type="password" name="senha_atual" required class="mt-1 w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                        </label>
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Nova senha</span>
                            <input type="password" name="nova_senha" required minlength="8" class="mt-1 w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                        </label>
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Confirmar senha</span>
                            <input type="password" name="confirmar_senha" required minlength="8" class="mt-1 w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                        </label>
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 dark:bg-slate-100 dark:hover:bg-white dark:text-slate-900 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="key-round" class="w-4 h-4"></i> Atualizar senha
                    </button>
                </form>
            </section>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../views/partials/confirm-modal.php'; ?>

<script>window.csrfToken = <?= json_encode(Csrf::token()) ?>;</script>
<script src="assets/js/api.js"></script>
<script src="assets/js/theme.js"></script>
<script src="assets/js/toast.js"></script>
<script src="assets/js/modal.js"></script>
<script src="assets/js/profile.js"></script>
</body>
</html>
