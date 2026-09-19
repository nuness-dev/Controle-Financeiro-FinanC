<div id="meta-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/50" data-modal-dismiss></div>
    <div class="absolute inset-0 flex items-start sm:items-center justify-center p-4 overflow-y-auto">
        <form id="meta-form" class="relative bg-white dark:bg-slate-900 rounded-xl shadow-xl w-full max-w-md my-8">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
            <input type="hidden" name="id" id="meta-id">

            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800">
                <h2 id="meta-modal-title" class="text-base font-semibold text-slate-900 dark:text-white">Nova meta</h2>
                <button type="button" data-modal-dismiss class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" aria-label="Fechar"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>

            <div class="px-5 py-4 space-y-4">
                <div>
                    <label for="meta-titulo" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Título *</label>
                    <input type="text" name="titulo" id="meta-titulo" required maxlength="120"
                        class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="meta-valor" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Valor objetivo *</label>
                        <input type="text" name="valor_objetivo" id="meta-valor" required inputmode="decimal" data-money-input placeholder="0,00"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label for="meta-prazo" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Prazo</label>
                        <input type="date" name="prazo" id="meta-prazo"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>
                <div>
                    <label for="meta-cor" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Cor</label>
                    <input type="color" name="cor" id="meta-cor" value="#22c55e" class="h-[38px] w-16 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800">
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-slate-200 dark:border-slate-800">
                <button type="button" data-modal-dismiss class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">Cancelar</button>
                <button type="submit" id="meta-submit" class="px-4 py-2 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-lg transition-colors">Salvar</button>
            </div>
        </form>
    </div>
</div>

<div id="contribuicao-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/50" data-modal-dismiss></div>
    <div class="absolute inset-0 flex items-start sm:items-center justify-center p-4 overflow-y-auto">
        <div class="relative bg-white dark:bg-slate-900 rounded-xl shadow-xl w-full max-w-md my-8">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800">
                <h2 id="contribuicao-modal-title" class="text-base font-semibold text-slate-900 dark:text-white">Meta</h2>
                <button type="button" data-modal-dismiss class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" aria-label="Fechar"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="px-5 py-4 space-y-4">
                <div>
                    <div class="flex items-center justify-between text-sm mb-1.5">
                        <span id="contribuicao-atual" class="font-semibold text-slate-900 dark:text-white"></span>
                        <span id="contribuicao-percentual" class="text-slate-500 dark:text-slate-400"></span>
                    </div>
                    <div class="progress-bar"><div id="contribuicao-barra" class="bg-brand-500" style="width:0%"></div></div>
                </div>

                <form id="contribuicao-form" class="flex items-end gap-2">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                    <div class="flex-1">
                        <label for="contribuicao-valor" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Adicionar valor</label>
                        <input type="text" name="valor" id="contribuicao-valor" required inputmode="decimal" data-money-input placeholder="0,00"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <button type="submit" id="contribuicao-submit" class="px-4 py-2 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-lg transition-colors">Adicionar</button>
                </form>

                <div>
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Histórico</p>
                    <div id="contribuicao-historico" class="space-y-2 max-h-48 overflow-y-auto"></div>
                </div>
            </div>
        </div>
    </div>
</div>
