const AccountsPage = {
    edicaoId: null,

    async init() {
        this.dom = {
            lista: document.getElementById('contas-lista'),
            form: document.getElementById('conta-form'),
            title: document.getElementById('conta-modal-title'),
            ativaWrap: document.getElementById('conta-ativa-wrap'),
        };

        document.getElementById('btn-nova-conta').addEventListener('click', () => this.abrirCriacao());
        this.dom.form.addEventListener('submit', (e) => this.salvar(e));

        await this.carregar();
    },

    async carregar() {
        this.dom.lista.innerHTML = `<div class="col-span-full py-10 text-center text-sm text-slate-400"><span class="spinner inline-block mr-2 align-middle"></span>Carregando contas…</div>`;
        try {
            const { contas } = await Api.get('api/accounts.php');
            this.renderizar(contas);
        } catch (erro) {
            this.dom.lista.innerHTML = `<div class="col-span-full py-10 text-center text-sm text-rose-500">${this.escapar(erro.message)}</div>`;
        }
    },

    renderizar(contas) {
        if (contas.length === 0) {
            this.dom.lista.innerHTML = `<div class="col-span-full py-10 text-center text-sm text-slate-400"><i data-lucide="wallet" class="w-8 h-8 mx-auto mb-2"></i><br>Nenhuma conta cadastrada ainda.<br>
                <button id="vazio-nova-conta" class="mt-3 inline-flex items-center gap-2 text-brand-600 dark:text-brand-400 font-medium text-sm"><i data-lucide="plus" class="w-4 h-4"></i> Criar primeira conta</button></div>`;
            Icons.refresh();
            document.getElementById('vazio-nova-conta')?.addEventListener('click', () => this.abrirCriacao());
            return;
        }

        this.dom.lista.innerHTML = contas.map((c) => `
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 ${c.ativa == 0 ? 'opacity-60' : ''}">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="w-3 h-3 rounded-full shrink-0" style="background:${c.cor}"></span>
                        <span class="font-semibold text-slate-900 dark:text-white truncate">${this.escapar(c.nome)}</span>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <button data-editar='${this.attrJson(c)}' class="p-1.5 text-slate-400 hover:text-brand-600" aria-label="Editar"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                        <button data-excluir="${c.id}" class="p-1.5 text-slate-400 hover:text-rose-600" aria-label="Excluir"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    </div>
                </div>
                <p class="text-xs text-slate-400 mb-1">${this.rotuloTipo(c.tipo)}${c.instituicao ? ' · ' + this.escapar(c.instituicao) : ''}</p>
                <p class="text-2xl font-bold ${Number(c.saldo.atual) < 0 ? 'text-rose-600' : 'text-slate-900 dark:text-white'}">${this.brl(c.saldo.atual)}</p>
                <p class="text-xs text-slate-400 mt-1">Previsto: ${this.brl(c.saldo.previsto)}</p>
            </div>`).join('');

        Icons.refresh();
        this.dom.lista.querySelectorAll('[data-editar]').forEach((btn) => {
            btn.addEventListener('click', () => this.abrirEdicao(JSON.parse(btn.dataset.editar)));
        });
        this.dom.lista.querySelectorAll('[data-excluir]').forEach((btn) => {
            btn.addEventListener('click', () => this.excluir(btn.dataset.excluir));
        });
    },

    abrirCriacao() {
        this.dom.form.reset();
        this.edicaoId = null;
        document.getElementById('conta-id').value = '';
        document.getElementById('conta-saldo').value = '0,00';
        this.dom.title.textContent = 'Nova conta';
        this.dom.ativaWrap.classList.add('hidden');
        Modal.open('conta-modal', '#conta-nome');
    },

    abrirEdicao(c) {
        this.dom.form.reset();
        this.edicaoId = c.id;
        document.getElementById('conta-id').value = c.id;
        document.getElementById('conta-nome').value = c.nome;
        document.getElementById('conta-tipo').value = c.tipo;
        document.getElementById('conta-saldo').value = Number(c.saldo_inicial).toFixed(2).replace('.', ',');
        document.getElementById('conta-instituicao').value = c.instituicao || '';
        document.getElementById('conta-cor').value = c.cor;
        document.getElementById('conta-ativa').checked = c.ativa == 1;
        this.dom.title.textContent = 'Editar conta';
        this.dom.ativaWrap.classList.remove('hidden');
        Modal.open('conta-modal', '#conta-nome');
    },

    async salvar(event) {
        event.preventDefault();
        const botao = document.getElementById('conta-submit');
        botao.disabled = true;
        const dados = Object.fromEntries(new FormData(this.dom.form).entries());
        dados.saldo_inicial = dados.saldo_inicial ? dados.saldo_inicial.replace(/\./g, '').replace(',', '.') : '0';
        dados.ativa = document.getElementById('conta-ativa').checked ? '1' : '0';

        try {
            if (this.edicaoId) {
                await Api.put(`api/accounts.php?id=${this.edicaoId}`, dados);
                Toast.success('Conta atualizada.');
            } else {
                await Api.post('api/accounts.php', dados);
                Toast.success('Conta criada.');
            }
            Modal.close('conta-modal');
            await this.carregar();
        } catch (erro) {
            Toast.error(erro.message);
        } finally {
            botao.disabled = false;
        }
    },

    async excluir(id) {
        const ok = await ConfirmModal.ask('Excluir conta?', 'Contas com movimentações não podem ser excluídas — desative-as em vez disso.');
        if (!ok) return;
        try {
            await Api.remove(`api/accounts.php?id=${id}`, { csrf_token: window.csrfToken });
            Toast.success('Conta excluída.');
            await this.carregar();
        } catch (erro) {
            Toast.error(erro.message);
        }
    },

    rotuloTipo(t) {
        return { corrente: 'Conta corrente', poupanca: 'Poupança', carteira: 'Carteira', digital: 'Conta digital', investimento: 'Investimento', outra: 'Outra' }[t] || t;
    },
    brl(v) { return 'R$ ' + Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
    attrJson(obj) { return this.escapar(JSON.stringify(obj)).replace(/"/g, '&quot;'); },
    escapar(texto) { const div = document.createElement('div'); div.textContent = texto ?? ''; return div.innerHTML; },
};

document.addEventListener('DOMContentLoaded', () => AccountsPage.init());
