<div id="conta-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/50" data-modal-dismiss></div>
    <div class="absolute inset-0 flex items-start sm:items-center justify-center p-4 overflow-y-auto">
        <form id="conta-form" class="relative bg-white dark:bg-slate-900 rounded-xl shadow-xl w-full max-w-md my-8">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
            <input type="hidden" name="id" id="conta-id">

            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800">
                <h2 id="conta-modal-title" class="text-base font-semibold text-slate-900 dark:text-white">Nova conta</h2>
                <button type="button" data-modal-dismiss class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" aria-label="Fechar"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>

            <div class="px-5 py-4 space-y-4">
                <div>
                    <label for="conta-nome" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Nome *</label>
                    <input type="text" name="nome" id="conta-nome" required maxlength="80"
                        class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="conta-tipo" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Tipo</label>
                        <select name="tipo" id="conta-tipo" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="corrente">Conta corrente</option>
                            <option value="poupanca">Poupança</option>
                            <option value="carteira">Carteira</option>
                            <option value="digital">Conta digital</option>
                            <option value="investimento">Investimento</option>
                            <option value="outra">Outra</option>
                        </select>
                    </div>
                    <div>
                        <label for="conta-saldo" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Saldo inicial</label>
                        <input type="text" name="saldo_inicial" id="conta-saldo" inputmode="decimal" data-money-input placeholder="0,00"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>
                <div>
                    <label for="conta-instituicao" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Instituição</label>
                    <input type="text" name="instituicao" id="conta-instituicao" maxlength="80"
                        class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div class="flex items-center gap-3">
                    <div>
                        <label for="conta-cor" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Cor</label>
                        <input type="color" name="cor" id="conta-cor" value="#3b82f6" class="h-[38px] w-16 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800">
                    </div>
                    <label id="conta-ativa-wrap" class="hidden flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300 mt-6">
                        <input type="checkbox" name="ativa" id="conta-ativa" checked class="rounded border-slate-300">
                        Conta ativa
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-slate-200 dark:border-slate-800">
                <button type="button" data-modal-dismiss class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">Cancelar</button>
                <button type="submit" id="conta-submit" class="px-4 py-2 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-lg transition-colors">Salvar</button>
            </div>
        </form>
    </div>
</div>
