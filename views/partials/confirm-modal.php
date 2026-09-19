<div id="confirm-modal" class="fixed inset-0 z-[70] hidden">
    <div class="absolute inset-0 bg-slate-900/50"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative bg-white dark:bg-slate-900 rounded-xl shadow-xl w-full max-w-sm p-6">
            <div class="flex items-start gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-rose-100 dark:bg-rose-500/15 flex items-center justify-center shrink-0">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600 dark:text-rose-400"></i>
                </div>
                <div>
                    <h2 id="confirm-modal-title" class="text-base font-semibold text-slate-900 dark:text-white">Confirmar ação</h2>
                    <p id="confirm-modal-message" class="text-sm text-slate-500 dark:text-slate-400 mt-1">Essa ação não poderá ser desfeita.</p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2">
                <button type="button" id="confirm-modal-cancel" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                    Cancelar
                </button>
                <button type="button" id="confirm-modal-ok" class="px-4 py-2 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg transition-colors">
                    Confirmar
                </button>
            </div>
        </div>
    </div>
</div>
