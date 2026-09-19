const CategoriesPage = {
    edicaoId: null,

    async init() {
        this.dom = {
            receita: document.getElementById('categorias-receita'),
            despesa: document.getElementById('categorias-despesa'),
            form: document.getElementById('categoria-form'),
            title: document.getElementById('categoria-modal-title'),
            tipoInput: document.getElementById('categoria-tipo'),
            ativaWrap: document.getElementById('categoria-ativa-wrap'),
        };

        document.getElementById('btn-nova-categoria').addEventListener('click', () => this.abrirCriacao());
        document.querySelectorAll('.tipo-categoria-btn').forEach((btn) => {
            btn.addEventListener('click', () => this.selecionarTipo(btn.dataset.tipoCategoria));
        });
        this.dom.form.addEventListener('submit', (e) => this.salvar(e));

        await this.carregar();
    },

    selecionarTipo(tipo) {
        this.dom.tipoInput.value = tipo;
        document.querySelectorAll('.tipo-categoria-btn').forEach((btn) => {
            const ativo = btn.dataset.tipoCategoria === tipo;
            btn.classList.toggle('bg-brand-600', ativo);
            btn.classList.toggle('text-white', ativo);
            btn.classList.toggle('border-brand-600', ativo);
            btn.classList.toggle('border-slate-300', !ativo);
            btn.classList.toggle('dark:border-slate-700', !ativo);
        });
    },

    async carregar() {
        try {
            const { categorias } = await Api.get('api/categories.php');
            this.renderizar(categorias.filter((c) => c.tipo === 'receita'), this.dom.receita);
            this.renderizar(categorias.filter((c) => c.tipo === 'despesa'), this.dom.despesa);
        } catch (erro) {
            Toast.error(erro.message);
        }
    },

    renderizar(lista, container) {
        if (lista.length === 0) {
            container.innerHTML = `<div class="col-span-full py-6 text-center text-sm text-slate-400">Nenhuma categoria aqui ainda.</div>`;
            return;
        }

        container.innerHTML = lista.map((c) => `
            <div class="flex items-center justify-between gap-3 p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 ${c.ativa == 0 ? 'opacity-60' : ''}">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="w-3 h-3 rounded-full shrink-0" style="background:${c.cor}"></span>
                    <span class="text-sm font-medium text-slate-800 dark:text-slate-100 truncate">${this.escapar(c.nome)}</span>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    <button data-editar='${this.attrJson(c)}' class="p-1.5 text-slate-400 hover:text-brand-600" aria-label="Editar"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                    <button data-excluir="${c.id}" class="p-1.5 text-slate-400 hover:text-rose-600" aria-label="Excluir"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                </div>
            </div>`).join('');

        Icons.refresh();
        container.querySelectorAll('[data-editar]').forEach((btn) => btn.addEventListener('click', () => this.abrirEdicao(JSON.parse(btn.dataset.editar))));
        container.querySelectorAll('[data-excluir]').forEach((btn) => btn.addEventListener('click', () => this.excluir(btn.dataset.excluir)));
    },

    abrirCriacao() {
        this.dom.form.reset();
        this.edicaoId = null;
        document.getElementById('categoria-id').value = '';
        this.dom.title.textContent = 'Nova categoria';
        this.dom.ativaWrap.classList.add('hidden');
        this.selecionarTipo('despesa');
        Modal.open('categoria-modal', '#categoria-nome');
    },

    abrirEdicao(c) {
        this.dom.form.reset();
        this.edicaoId = c.id;
        document.getElementById('categoria-id').value = c.id;
        document.getElementById('categoria-nome').value = c.nome;
        document.getElementById('categoria-cor').value = c.cor;
        document.getElementById('categoria-icone').value = c.icone || '';
        document.getElementById('categoria-ativa').checked = c.ativa == 1;
        this.dom.title.textContent = 'Editar categoria';
        this.dom.ativaWrap.classList.remove('hidden');
        this.selecionarTipo(c.tipo);
        Modal.open('categoria-modal', '#categoria-nome');
    },

    async salvar(event) {
        event.preventDefault();
        const botao = document.getElementById('categoria-submit');
        botao.disabled = true;
        const dados = Object.fromEntries(new FormData(this.dom.form).entries());
        dados.ativa = document.getElementById('categoria-ativa').checked ? '1' : '0';

        try {
            if (this.edicaoId) {
                await Api.put(`api/categories.php?id=${this.edicaoId}`, dados);
                Toast.success('Categoria atualizada.');
            } else {
                await Api.post('api/categories.php', dados);
                Toast.success('Categoria criada.');
            }
            Modal.close('categoria-modal');
            await this.carregar();
        } catch (erro) {
            Toast.error(erro.message);
        } finally {
            botao.disabled = false;
        }
    },

    async excluir(id) {
        const ok = await ConfirmModal.ask('Excluir categoria?', 'Transações associadas ficam sem categoria.');
        if (!ok) return;
        try {
            await Api.remove(`api/categories.php?id=${id}`, { csrf_token: window.csrfToken });
            Toast.success('Categoria excluída.');
            await this.carregar();
        } catch (erro) {
            Toast.error(erro.message);
        }
    },

    attrJson(obj) { const div = document.createElement('div'); div.textContent = JSON.stringify(obj); return div.innerHTML; },
    escapar(texto) { const div = document.createElement('div'); div.textContent = texto ?? ''; return div.innerHTML; },
};

document.addEventListener('DOMContentLoaded', () => CategoriesPage.init());
