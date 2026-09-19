<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;

Session::start();

if (Auth::check()) {
    header('Location: dashboard.php');
    exit;
}

$title = 'Criar conta — FinanC';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<?php include __DIR__ . '/../views/partials/head.php'; ?>
</head>
<body class="min-h-screen bg-slate-50 dark:bg-slate-950 flex items-center justify-center px-4">
    <div id="toast-container" class="pointer-events-none"></div>

    <div class="w-full max-w-sm bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 p-8">
        <div class="flex flex-col items-center mb-6">
            <div class="w-11 h-11 rounded-xl bg-brand-600 flex items-center justify-center mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6">
                    <line x1="12" y1="2" x2="12" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
            </div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-white">Criar conta</h1>
            <p class="text-sm text-slate-400 mt-1">Leva menos de um minuto.</p>
        </div>

        <form id="cadastro-form" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
            <div>
                <label for="nome" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Nome</label>
                <input type="text" name="nome" id="nome" required autofocus
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Email</label>
                <input type="email" name="email" id="email" required
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <label for="senha" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Senha</label>
                <input type="password" name="senha" id="senha" required minlength="8"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                <p class="text-xs text-slate-400 mt-1">Mínimo de 8 caracteres.</p>
            </div>
            <div>
                <label for="confirmar_senha" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Confirmar senha</label>
                <input type="password" name="confirmar_senha" id="confirmar_senha" required minlength="8"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <button type="submit" id="cadastro-submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors">
                Criar conta
            </button>
        </form>

        <p class="text-center text-sm text-slate-500 dark:text-slate-400 mt-6">
            Já possui uma conta? <a href="login.php" class="text-brand-600 dark:text-brand-400 font-medium hover:underline">Entrar</a>
        </p>
    </div>

    <script src="assets/js/api.js"></script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/toast.js"></script>
    <script>
        document.getElementById('cadastro-form').addEventListener('submit', async (event) => {
            event.preventDefault();
            const botao = document.getElementById('cadastro-submit');
            botao.disabled = true;
            const dados = Object.fromEntries(new FormData(event.target).entries());

            try {
                await Api.post('api/auth.php?action=registrar', dados);
                window.location.href = 'dashboard.php';
            } catch (erro) {
                Toast.error(erro.message);
                botao.disabled = false;
            }
        });
    </script>
</body>
</html>
