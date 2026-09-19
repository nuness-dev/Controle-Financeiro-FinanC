<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Csrf;
use App\Core\Session;

Session::start();

$token = (string) ($_GET['token'] ?? '');
$title = 'Redefinir senha — FinanC';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<?php include __DIR__ . '/../views/partials/head.php'; ?>
</head>
<body class="min-h-screen bg-slate-50 dark:bg-slate-950 flex items-center justify-center px-4">
    <div id="toast-container" class="pointer-events-none"></div>

    <div class="w-full max-w-sm bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 p-8">
        <h1 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Redefinir senha</h1>
        <p class="text-sm text-slate-400 mb-6">Escolha uma nova senha para sua conta.</p>

        <form id="redefinir-form" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <div>
                <label for="senha" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Nova senha</label>
                <input type="password" name="senha" id="senha" required minlength="8"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <label for="confirmar_senha" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Confirmar nova senha</label>
                <input type="password" name="confirmar_senha" id="confirmar_senha" required minlength="8"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <button type="submit" id="redefinir-submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors">
                Redefinir senha
            </button>
        </form>
    </div>

    <script src="assets/js/api.js"></script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/toast.js"></script>
    <script>
        document.getElementById('redefinir-form').addEventListener('submit', async (event) => {
            event.preventDefault();
            const botao = document.getElementById('redefinir-submit');
            botao.disabled = true;
            const dados = Object.fromEntries(new FormData(event.target).entries());

            try {
                const resultado = await Api.post('api/auth.php?action=redefinir-senha', dados);
                Toast.success(resultado.message);
                setTimeout(() => window.location.href = 'login.php', 1500);
            } catch (erro) {
                Toast.error(erro.message);
                botao.disabled = false;
            }
        });
    </script>
</body>
</html>
