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

$title = 'Entrar — FinanC';
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
            <h1 class="text-lg font-bold text-slate-900 dark:text-white">Entrar no FinanC</h1>
            <p class="text-sm text-slate-400 mt-1">Controle financeiro pessoal.</p>
        </div>

        <form id="login-form" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Email</label>
                <input type="email" name="email" id="email" required autofocus
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="senha" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Senha</label>
                    <a href="esqueci-senha.php" class="text-xs text-brand-600 dark:text-brand-400 hover:underline">Esqueci minha senha</a>
                </div>
                <input type="password" name="senha" id="senha" required
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <button type="submit" id="login-submit" class="w-full flex items-center justify-center gap-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors">
                Entrar
            </button>
        </form>

        <p class="text-center text-sm text-slate-500 dark:text-slate-400 mt-6">
            Não possui uma conta? <a href="cadastro.php" class="text-brand-600 dark:text-brand-400 font-medium hover:underline">Criar conta</a>
        </p>
        <p class="text-center text-xs text-slate-400 mt-4">Demo: usuario@financ.dev / usuario123</p>
    </div>

    <script src="assets/js/api.js"></script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/toast.js"></script>
    <script>
        document.getElementById('login-form').addEventListener('submit', async (event) => {
            event.preventDefault();
            const botao = document.getElementById('login-submit');
            botao.disabled = true;
            const dados = Object.fromEntries(new FormData(event.target).entries());

            try {
                await Api.post('api/auth.php?action=login', dados);
                window.location.href = 'dashboard.php';
            } catch (erro) {
                Toast.error(erro.message);
                botao.disabled = false;
            }
        });
    </script>
</body>
</html>
