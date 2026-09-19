<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Csrf;
use App\Core\Session;

Session::start();

$title = 'Recuperar senha — FinanC';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<?php include __DIR__ . '/../views/partials/head.php'; ?>
</head>
<body class="min-h-screen bg-slate-50 dark:bg-slate-950 flex items-center justify-center px-4">
    <div id="toast-container" class="pointer-events-none"></div>

    <div class="w-full max-w-sm bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 p-8">
        <h1 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Recuperar senha</h1>
        <p class="text-sm text-slate-400 mb-6">Demonstração local — nenhum email é enviado de verdade; o link é exibido na tela.</p>

        <form id="esqueci-form" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Email</label>
                <input type="email" name="email" id="email" required autofocus
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <button type="submit" id="esqueci-submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors">
                Gerar link de redefinição
            </button>
        </form>

        <div id="reset-link-box" class="hidden mt-4 p-3 rounded-lg bg-brand-50 dark:bg-brand-500/10 border border-brand-200 dark:border-brand-500/20 text-sm">
            <p class="text-slate-600 dark:text-slate-300 mb-1">Link de redefinição (simulando o email):</p>
            <a id="reset-link" href="#" class="text-brand-700 dark:text-brand-300 font-medium break-all hover:underline"></a>
        </div>

        <p class="text-center text-sm text-slate-500 dark:text-slate-400 mt-6">
            <a href="login.php" class="text-brand-600 dark:text-brand-400 font-medium hover:underline">Voltar para o login</a>
        </p>
    </div>

    <script src="assets/js/api.js"></script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/toast.js"></script>
    <script>
        document.getElementById('esqueci-form').addEventListener('submit', async (event) => {
            event.preventDefault();
            const botao = document.getElementById('esqueci-submit');
            botao.disabled = true;
            const dados = Object.fromEntries(new FormData(event.target).entries());

            try {
                const resultado = await Api.post('api/auth.php?action=esqueci-senha', dados);
                Toast.success(resultado.message);
                if (resultado.reset_link) {
                    document.getElementById('reset-link-box').classList.remove('hidden');
                    const link = document.getElementById('reset-link');
                    link.href = resultado.reset_link;
                    link.textContent = resultado.reset_link;
                }
            } catch (erro) {
                Toast.error(erro.message);
            } finally {
                botao.disabled = false;
            }
        });
    </script>
</body>
</html>
