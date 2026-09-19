<div id="cartao-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/50" data-modal-dismiss></div>
    <div class="absolute inset-0 flex items-start sm:items-center justify-center p-4 overflow-y-auto">
        <form id="cartao-form" class="relative bg-white dark:bg-slate-900 rounded-xl shadow-xl w-full max-w-md my-8">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
            <input type="hidden" name="id" id="cartao-id">

            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800">
                <h2 id="cartao-modal-title" class="text-base font-semibold text-slate-900 dark:text-white">Novo cartão</h2>
                <button type="button" data-modal-dismiss class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" aria-label="Fechar"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>

            <div class="px-5 py-4 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="cartao-nome" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Nome *</label>
                        <input type="text" name="nome" id="cartao-nome" required maxlength="80"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label for="cartao-banco" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Banco</label>
                        <input type="text" name="banco" id="cartao-banco" maxlength="80"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="cartao-digitos" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Últimos 4 dígitos *</label>
                        <input type="text" name="ultimos_digitos" id="cartao-digitos" required maxlength="4" pattern="\d{4}" inputmode="numeric"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label for="cartao-limite" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Limite</label>
                        <input type="text" name="limite" id="cartao-limite" inputmode="decimal" data-money-input placeholder="0,00"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="cartao-fechamento" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Dia de fechamento *</label>
                        <input type="number" name="dia_fechamento" id="cartao-fechamento" required min="1" max="28"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label for="cartao-vencimento" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Dia de vencimento *</label>
                        <input type="number" name="dia_vencimento" id="cartao-vencimento" required min="1" max="28"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div>
                        <label for="cartao-cor" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Cor</label>
                        <input type="color" name="cor" id="cartao-cor" value="#8b5cf6" class="h-[38px] w-16 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800">
                    </div>
                    <label id="cartao-ativo-wrap" class="hidden flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300 mt-6">
                        <input type="checkbox" name="ativo" id="cartao-ativo" checked class="rounded border-slate-300">
                        Cartão ativo
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-slate-200 dark:border-slate-800">
                <button type="button" data-modal-dismiss class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">Cancelar</button>
                <button type="submit" id="cartao-submit" class="px-4 py-2 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-lg transition-colors">Salvar</button>
            </div>
        </form>
    </div>
</div>
