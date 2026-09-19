const CardsPage = {
    edicaoId: null,

    async init() {
        this.dom = {
            lista: document.getElementById('cartoes-lista'),
            form: document.getElementById('cartao-form'),
            title: document.getElementById('cartao-modal-title'),
            ativoWrap: document.getElementById('cartao-ativo-wrap'),
        };

        document.getElementById('btn-novo-cartao').addEventListener('click', () => this.abrirCriacao());
        this.dom.form.addEventListener('submit', (e) => this.salvar(e));

        await this.carregar();
    },

    async carregar() {
        this.dom.lista.innerHTML = `<div class="col-span-full py-10 text-center text-sm text-slate-400"><span class="spinner inline-block mr-2 align-middle"></span>Carregando cartões…</div>`;
        try {
            const { cartoes } = await Api.get('api/cards.php');
            this.renderizar(cartoes);
        } catch (erro) {
            this.dom.lista.innerHTML = `<div class="col-span-full py-10 text-center text-sm text-rose-500">${this.escapar(erro.message)}</div>`;
        }
    },

    renderizar(cartoes) {
        if (cartoes.length === 0) {
            this.dom.lista.innerHTML = `<div class="col-span-full py-10 text-center text-sm text-slate-400"><i data-lucide="credit-card" class="w-8 h-8 mx-auto mb-2"></i><br>Nenhum cartão cadastrado ainda.<br>
                <button id="vazio-novo-cartao" class="mt-3 inline-flex items-center gap-2 text-brand-600 dark:text-brand-400 font-medium text-sm"><i data-lucide="plus" class="w-4 h-4"></i> Criar primeiro cartão</button></div>`;
            Icons.refresh();
            document.getElementById('vazio-novo-cartao')?.addEventListener('click', () => this.abrirCriacao());
            return;
        }

        this.dom.lista.innerHTML = cartoes.map((c) => `
            <a href="faturas.php?card_id=${c.id}" class="block rounded-xl p-4 text-white ${c.ativo == 0 ? 'opacity-50' : ''}" style="background:linear-gradient(135deg, ${c.cor}, ${this.escurecer(c.cor)})">
                <div class="flex items-center justify-between mb-6">
                    <i data-lucide="credit-card" class="w-6 h-6"></i>
                    <div class="flex items-center gap-1">
                        <button data-editar='${this.attrJson(c)}' class="p-1 hover:bg-white/20 rounded" aria-label="Editar" onclick="event.preventDefault()"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                        <button data-excluir="${c.id}" class="p-1 hover:bg-white/20 rounded" aria-label="Excluir" onclick="event.preventDefault()"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    </div>
                </div>
                <p class="font-semibold">${this.escapar(c.nome)}</p>
                <p class="text-sm opacity-90">${this.escapar(c.banco || '')} •••• ${c.ultimos_digitos}</p>
                <p class="text-xs opacity-75 mt-3">Limite: R$ ${Number(c.limite).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</p>
                <p class="text-xs opacity-75">Fecha dia ${c.dia_fechamento} · Vence dia ${c.dia_vencimento}</p>
            </a>`).join('');

        Icons.refresh();
        this.dom.lista.querySelectorAll('[data-editar]').forEach((btn) => btn.addEventListener('click', (e) => { e.stopPropagation(); this.abrirEdicao(JSON.parse(btn.dataset.editar)); }));
        this.dom.lista.querySelectorAll('[data-excluir]').forEach((btn) => btn.addEventListener('click', (e) => { e.stopPropagation(); this.excluir(btn.dataset.excluir); }));
    },

    abrirCriacao() {
        this.dom.form.reset();
        this.edicaoId = null;
        document.getElementById('cartao-id').value = '';
        this.dom.title.textContent = 'Novo cartão';
        this.dom.ativoWrap.classList.add('hidden');
        Modal.open('cartao-modal', '#cartao-nome');
    },

    abrirEdicao(c) {
        this.dom.form.reset();
        this.edicaoId = c.id;
        document.getElementById('cartao-id').value = c.id;
        document.getElementById('cartao-nome').value = c.nome;
        document.getElementById('cartao-banco').value = c.banco || '';
        document.getElementById('cartao-digitos').value = c.ultimos_digitos;
        document.getElementById('cartao-limite').value = Number(c.limite).toFixed(2).replace('.', ',');
        document.getElementById('cartao-fechamento').value = c.dia_fechamento;
        document.getElementById('cartao-vencimento').value = c.dia_vencimento;
        document.getElementById('cartao-cor').value = c.cor;
        document.getElementById('cartao-ativo').checked = c.ativo == 1;
        this.dom.title.textContent = 'Editar cartão';
        this.dom.ativoWrap.classList.remove('hidden');
        Modal.open('cartao-modal', '#cartao-nome');
    },

    async salvar(event) {
        event.preventDefault();
        const botao = document.getElementById('cartao-submit');
        botao.disabled = true;
        const dados = Object.fromEntries(new FormData(this.dom.form).entries());
        dados.limite = dados.limite ? dados.limite.replace(/\./g, '').replace(',', '.') : '0';
        dados.ativo = document.getElementById('cartao-ativo').checked ? '1' : '0';

        try {
            if (this.edicaoId) {
                await Api.put(`api/cards.php?id=${this.edicaoId}`, dados);
                Toast.success('Cartão atualizado.');
            } else {
                await Api.post('api/cards.php', dados);
                Toast.success('Cartão criado.');
            }
            Modal.close('cartao-modal');
            await this.carregar();
        } catch (erro) {
            Toast.error(erro.message);
        } finally {
            botao.disabled = false;
        }
    },

    async excluir(id) {
        const ok = await ConfirmModal.ask('Excluir cartão?', 'Essa ação não poderá ser desfeita.');
        if (!ok) return;
        try {
            await Api.remove(`api/cards.php?id=${id}`, { csrf_token: window.csrfToken });
            Toast.success('Cartão excluído.');
            await this.carregar();
        } catch (erro) {
            Toast.error(erro.message);
        }
    },

    escurecer(hex) {
        const n = parseInt(hex.slice(1), 16);
        const r = Math.max(0, (n >> 16) - 40), g = Math.max(0, ((n >> 8) & 0xff) - 40), b = Math.max(0, (n & 0xff) - 40);
        return `rgb(${r},${g},${b})`;
    },
    attrJson(obj) { const div = document.createElement('div'); div.textContent = JSON.stringify(obj); return div.innerHTML; },
    escapar(texto) { const div = document.createElement('div'); div.textContent = texto ?? ''; return div.innerHTML; },
};

document.addEventListener('DOMContentLoaded', () => CardsPage.init());
