<div id="categoria-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/50" data-modal-dismiss></div>
    <div class="absolute inset-0 flex items-start sm:items-center justify-center p-4 overflow-y-auto">
        <form id="categoria-form" class="relative bg-white dark:bg-slate-900 rounded-xl shadow-xl w-full max-w-md my-8">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
            <input type="hidden" name="id" id="categoria-id">

            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800">
                <h2 id="categoria-modal-title" class="text-base font-semibold text-slate-900 dark:text-white">Nova categoria</h2>
                <button type="button" data-modal-dismiss class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" aria-label="Fechar"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>

            <div class="px-5 py-4 space-y-4">
                <div>
                    <label for="categoria-nome" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Nome *</label>
                    <input type="text" name="nome" id="categoria-nome" required maxlength="80"
                        class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div class="grid grid-cols-3 gap-2" role="group" aria-label="Tipo de categoria">
                    <button type="button" data-tipo-categoria="receita" class="tipo-categoria-btn px-3 py-2 rounded-lg text-sm font-semibold border transition-colors col-span-1">Receita</button>
                    <button type="button" data-tipo-categoria="despesa" class="tipo-categoria-btn px-3 py-2 rounded-lg text-sm font-semibold border transition-colors col-span-1">Despesa</button>
                    <div>
                        <input type="color" name="cor" id="categoria-cor" value="#6366f1" class="h-[38px] w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800">
                    </div>
                </div>
                <input type="hidden" name="tipo" id="categoria-tipo" value="despesa">
                <div>
                    <label for="categoria-icone" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Ícone (nome do Lucide, opcional)</label>
                    <input type="text" name="icone" id="categoria-icone" placeholder="ex.: utensils, car, home"
                        class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <label id="categoria-ativa-wrap" class="hidden flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                    <input type="checkbox" name="ativa" id="categoria-ativa" checked class="rounded border-slate-300">
                    Categoria ativa
                </label>
            </div>

            <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-slate-200 dark:border-slate-800">
                <button type="button" data-modal-dismiss class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">Cancelar</button>
                <button type="submit" id="categoria-submit" class="px-4 py-2 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-lg transition-colors">Salvar</button>
            </div>
        </form>
    </div>
</div>
