const InvoicesPage = {
    async init() {
        this.dom = {
            lista: document.getElementById('faturas-lista'),
            compraForm: document.getElementById('compra-form'),
            compraCategoria: document.getElementById('compra-categoria'),
            pagarForm: document.getElementById('pagar-form'),
            pagarConta: document.getElementById('pagar-conta'),
        };

        document.getElementById('btn-nova-compra').addEventListener('click', () => {
            this.dom.compraForm.reset();
            document.getElementById('compra-data').value = new Date().toISOString().slice(0, 10);
            Modal.open('compra-modal', '#compra-descricao');
        });
        this.dom.compraForm.addEventListener('submit', (e) => this.registrarCompra(e));
        this.dom.pagarForm.addEventListener('submit', (e) => this.pagar(e));

        await this.carregarCategorias();
        await this.carregarContas();
        await this.carregar();
    },

    async carregarCategorias() {
        const { categorias } = await Api.get('api/categories.php?tipo=despesa');
        this.dom.compraCategoria.insertAdjacentHTML('beforeend', categorias.map((c) => `<option value="${c.id}">${this.escapar(c.nome)}</option>`).join(''));
    },

    async carregarContas() {
        const { contas } = await Api.get('api/accounts.php');
        this.dom.pagarConta.innerHTML = contas.map((c) => `<option value="${c.id}">${this.escapar(c.nome)}</option>`).join('');
    },

    async carregar() {
        this.dom.lista.innerHTML = `<div class="col-span-full py-10 text-center text-sm text-slate-400"><span class="spinner inline-block mr-2 align-middle"></span>Carregando faturas…</div>`;
        try {
            const { faturas } = await Api.get(`api/invoices.php?card_id=${window.cardId}`);
            this.renderizar(faturas);
        } catch (erro) {
            this.dom.lista.innerHTML = `<div class="col-span-full py-10 text-center text-sm text-rose-500">${this.escapar(erro.message)}</div>`;
        }
    },

    renderizar(faturas) {
        if (faturas.length === 0) {
            this.dom.lista.innerHTML = `<div class="col-span-full py-10 text-center text-sm text-slate-400"><i data-lucide="receipt" class="w-8 h-8 mx-auto mb-2"></i><br>Nenhuma fatura ainda. Registre uma compra para começar.</div>`;
            Icons.refresh();
            return;
        }

        this.dom.lista.innerHTML = faturas.map((f) => `
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <p class="font-semibold text-slate-900 dark:text-white">${this.mesNome(f.mes_referencia)}</p>
                    <span class="badge badge-${f.status}">${this.rotuloStatus(f.status)}</span>
                </div>
                <p class="text-2xl font-bold text-slate-900 dark:text-white mb-1">R$ ${Number(f.valor_total).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</p>
                <p class="text-xs text-slate-400">Fecha em ${this.dataBr(f.data_fechamento)} · Vence em ${this.dataBr(f.data_vencimento)}</p>
                ${f.status !== 'paga' ? `<button data-pagar="${f.id}" class="mt-3 w-full flex items-center justify-center gap-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold py-2 rounded-lg">Pagar fatura</button>` : ''}
            </div>`).join('');

        Icons.refresh();
        this.dom.lista.querySelectorAll('[data-pagar]').forEach((btn) => {
            btn.addEventListener('click', () => {
                document.getElementById('pagar-fatura-id').value = btn.dataset.pagar;
                Modal.open('pagar-modal', '#pagar-conta');
            });
        });
    },

    async registrarCompra(event) {
        event.preventDefault();
        const botao = document.getElementById('compra-submit');
        botao.disabled = true;
        const dados = Object.fromEntries(new FormData(this.dom.compraForm).entries());
        dados.card_id = String(window.cardId);
        dados.valor_total = dados.valor_total ? dados.valor_total.replace(/\./g, '').replace(',', '.') : '';

        try {
            await Api.post('api/invoices.php?action=compra', dados);
            Toast.success('Compra registrada.');
            Modal.close('compra-modal');
            await this.carregar();
        } catch (erro) {
            Toast.error(erro.message);
        } finally {
            botao.disabled = false;
        }
    },

    async pagar(event) {
        event.preventDefault();
        const botao = document.getElementById('pagar-submit');
        botao.disabled = true;
        const id = document.getElementById('pagar-fatura-id').value;
        const dados = Object.fromEntries(new FormData(this.dom.pagarForm).entries());

        try {
            await Api.post(`api/invoices.php?action=pagar&id=${id}`, dados);
            Toast.success('Fatura paga com sucesso.');
            Modal.close('pagar-modal');
            await this.carregar();
        } catch (erro) {
            Toast.error(erro.message);
        } finally {
            botao.disabled = false;
        }
    },

    rotuloStatus(s) { return { aberta: 'Aberta', fechada: 'Fechada', paga: 'Paga', atrasada: 'Atrasada' }[s] || s; },
    dataBr(iso) { const [a, m, d] = iso.slice(0, 10).split('-'); return `${d}/${m}/${a}`; },
    mesNome(mesRef) {
        const [ano, mes] = mesRef.split('-');
        const nomes = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        return `${nomes[Number(mes) - 1]}/${ano}`;
    },
    escapar(texto) { const div = document.createElement('div'); div.textContent = texto ?? ''; return div.innerHTML; },
};

document.addEventListener('DOMContentLoaded', () => InvoicesPage.init());
