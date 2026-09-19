<div id="transacao-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/50" data-modal-dismiss></div>

    <div class="absolute inset-0 flex items-start sm:items-center justify-center p-4 overflow-y-auto">
        <form id="transacao-form" class="relative bg-white dark:bg-slate-900 rounded-xl shadow-xl w-full max-w-xl my-8">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
            <input type="hidden" name="id" id="transacao-id">

            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800">
                <h2 id="transacao-modal-title" class="text-base font-semibold text-slate-900 dark:text-white">Nova transação</h2>
                <button type="button" data-modal-dismiss class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" aria-label="Fechar">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="px-5 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                <div class="grid grid-cols-3 gap-2" role="group" aria-label="Tipo de transação">
                    <button type="button" data-tipo="receita" class="tipo-btn px-3 py-2 rounded-lg text-sm font-semibold border transition-colors">Receita</button>
                    <button type="button" data-tipo="despesa" class="tipo-btn px-3 py-2 rounded-lg text-sm font-semibold border transition-colors">Despesa</button>
                    <button type="button" data-tipo="transferencia" class="tipo-btn px-3 py-2 rounded-lg text-sm font-semibold border transition-colors">Transferência</button>
                </div>
                <input type="hidden" name="tipo" id="transacao-tipo" value="despesa">

                <div>
                    <label for="transacao-descricao" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Descrição *</label>
                    <input type="text" name="descricao" id="transacao-descricao" required maxlength="180"
                        class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="transacao-valor" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Valor *</label>
                        <input type="text" name="valor" id="transacao-valor" required inputmode="decimal" data-money-input placeholder="0,00"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label for="transacao-data" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Data *</label>
                        <input type="date" name="data" id="transacao-data" required
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="transacao-conta" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5" id="label-conta">Conta *</label>
                        <select name="account_id" id="transacao-conta" required
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500"></select>
                    </div>
                    <div id="campo-conta-destino" class="hidden">
                        <label for="transacao-conta-destino" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Conta de destino *</label>
                        <select name="conta_destino_id" id="transacao-conta-destino"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500"></select>
                    </div>
                    <div id="campo-categoria">
                        <label for="transacao-categoria" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Categoria</label>
                        <select name="category_id" id="transacao-categoria"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="">Sem categoria</option>
                        </select>
                    </div>
                </div>

                <div id="campos-status-vencimento" class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="transacao-status" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Status</label>
                        <select name="status" id="transacao-status"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="pendente">Pendente</option>
                            <option value="pago">Pago</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                    <div>
                        <label for="transacao-vencimento" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Vencimento</label>
                        <input type="date" name="vencimento" id="transacao-vencimento"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>

                <div id="campos-recorrencia-parcela" class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="transacao-recorrencia" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Recorrência</label>
                        <select name="recorrencia_tipo" id="transacao-recorrencia"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="">Não repetir</option>
                            <option value="semanal">Semanal</option>
                            <option value="mensal">Mensal</option>
                            <option value="anual">Anual</option>
                        </select>
                        <p class="text-xs text-slate-400 mt-1">Gera 12 ocorrências futuras.</p>
                    </div>
                    <div>
                        <label for="transacao-parcelas" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Parcelar em</label>
                        <input type="number" name="parcela_total" id="transacao-parcelas" min="1" max="60" value="1"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>

                <div>
                    <label for="transacao-observacao" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Observação</label>
                    <textarea name="observacao" id="transacao-observacao" rows="2"
                        class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500"></textarea>
                </div>

                <div id="bloco-anexos" class="hidden">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Anexos</label>
                    <div id="anexos-lista" class="space-y-2 mb-2"></div>
                    <label class="inline-flex items-center gap-2 text-sm text-brand-600 dark:text-brand-400 cursor-pointer">
                        <i data-lucide="paperclip" class="w-4 h-4"></i>
                        Adicionar anexo (JPG, PNG, WEBP ou PDF, até 5MB)
                        <input type="file" id="transacao-anexo-input" accept=".jpg,.jpeg,.png,.webp,.pdf" class="hidden">
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-slate-200 dark:border-slate-800">
                <button type="button" data-modal-dismiss class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                    Cancelar
                </button>
                <button type="submit" id="transacao-submit" class="flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-lg transition-colors">
                    Salvar
                </button>
            </div>
        </form>
    </div>
</div>
