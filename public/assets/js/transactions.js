const TransactionsPage = {
    state: { page: 1, perPage: 15, sort: 'data', direction: 'desc' },
    contas: [],
    categorias: [],
    edicaoId: null,

    async init() {
        this.cacheDom();
        this.bindEvents();
        await Promise.all([this.carregarContas(), this.carregarCategorias()]);
        await this.carregarTransacoes();
    },

    cacheDom() {
        this.dom = {
            tbody: document.getElementById('transacoes-tbody'),
            count: document.getElementById('transacoes-count'),
            pagination: document.getElementById('transacoes-pagination'),
            busca: document.getElementById('filtro-busca'),
            tipo: document.getElementById('filtro-tipo'),
            status: document.getElementById('filtro-status'),
            categoria: document.getElementById('filtro-categoria'),
            conta: document.getElementById('filtro-conta'),
            dataInicio: document.getElementById('filtro-data-inicio'),
            dataFim: document.getElementById('filtro-data-fim'),
            valorMin: document.getElementById('filtro-valor-min'),
            valorMax: document.getElementById('filtro-valor-max'),
            form: document.getElementById('transacao-form'),
            modalTitle: document.getElementById('transacao-modal-title'),
            tipoInput: document.getElementById('transacao-tipo'),
            contaSelect: document.getElementById('transacao-conta'),
            contaDestinoSelect: document.getElementById('transacao-conta-destino'),
            categoriaSelect: document.getElementById('transacao-categoria'),
            campoContaDestino: document.getElementById('campo-conta-destino'),
            campoCategoria: document.getElementById('campo-categoria'),
            camposStatusVenc: document.getElementById('campos-status-vencimento'),
            camposRecParcela: document.getElementById('campos-recorrencia-parcela'),
            blocoAnexos: document.getElementById('bloco-anexos'),
            anexosLista: document.getElementById('anexos-lista'),
            anexoInput: document.getElementById('transacao-anexo-input'),
        };
    },

    bindEvents() {
        let debounce;
        this.dom.busca.addEventListener('input', () => {
            clearTimeout(debounce);
            debounce = setTimeout(() => { this.state.page = 1; this.carregarTransacoes(); }, 350);
        });

        ['tipo', 'status', 'categoria', 'conta', 'dataInicio', 'dataFim'].forEach((campo) => {
            this.dom[campo].addEventListener('change', () => { this.state.page = 1; this.carregarTransacoes(); });
        });
        [this.dom.valorMin, this.dom.valorMax].forEach((el) => {
            el.addEventListener('change', () => { this.state.page = 1; this.carregarTransacoes(); });
        });

        document.getElementById('btn-filtros')?.addEventListener('click', () => {
            document.getElementById('painel-filtros').classList.toggle('hidden');
        });

        document.querySelectorAll('[data-sort]').forEach((th) => {
            th.addEventListener('click', () => {
                const coluna = th.dataset.sort;
                if (this.state.sort === coluna) {
                    this.state.direction = this.state.direction === 'asc' ? 'desc' : 'asc';
                } else {
                    this.state.sort = coluna;
                    this.state.direction = 'asc';
                }
                this.carregarTransacoes();
            });
        });

        document.getElementById('btn-nova-transacao').addEventListener('click', () => this.abrirCriacao());
        document.querySelectorAll('.tipo-btn').forEach((btn) => {
            btn.addEventListener('click', () => this.selecionarTipo(btn.dataset.tipo));
        });

        this.dom.form.addEventListener('submit', (event) => this.salvar(event));

        this.dom.anexoInput.addEventListener('change', () => this.enviarAnexo());
    },

    async carregarContas() {
        const { contas } = await Api.get('api/accounts.php');
        this.contas = contas;

        const opcoes = contas.map((c) => `<option value="${c.id}">${this.escapar(c.nome)}</option>`).join('');
        this.dom.conta.insertAdjacentHTML('beforeend', opcoes);
        this.dom.contaSelect.innerHTML = opcoes;
        this.dom.contaDestinoSelect.innerHTML = opcoes;
    },

    async carregarCategorias() {
        const { categorias } = await Api.get('api/categories.php');
        this.categorias = categorias;

        const opcoes = categorias.map((c) => `<option value="${c.id}" data-tipo="${c.tipo}">${this.escapar(c.nome)}</option>`).join('');
        this.dom.categoria.insertAdjacentHTML('beforeend', opcoes);
        this.dom.categoriaSelect.insertAdjacentHTML('beforeend', opcoes);
    },

    async carregarTransacoes() {
        this.dom.tbody.innerHTML = this.linhaEstado('Carregando transações…', true);

        const params = new URLSearchParams({
            page: this.state.page, per_page: this.state.perPage,
            sort: this.state.sort, direction: this.state.direction,
        });

        const filtros = {
            search: this.dom.busca.value.trim(),
            tipo: this.dom.tipo.value, status: this.dom.status.value,
            category_id: this.dom.categoria.value, account_id: this.dom.conta.value,
            data_inicio: this.dom.dataInicio.value, data_fim: this.dom.dataFim.value,
            valor_min: this.desmascarar(this.dom.valorMin.value), valor_max: this.desmascarar(this.dom.valorMax.value),
        };
        for (const [chave, valor] of Object.entries(filtros)) {
            if (valor) params.set(chave, valor);
        }

        try {
            const resultado = await Api.get(`api/transactions.php?${params.toString()}`);
            this.renderizar(resultado);
        } catch (erro) {
            this.dom.tbody.innerHTML = this.linhaEstado(erro.message, false);
        }
    },

    renderizar(resultado) {
        const { dados, total, pagina, totalPaginas } = resultado;
        this.dom.count.textContent = `${total} transação${total === 1 ? '' : 'ões'} encontrada${total === 1 ? '' : 's'}`;

        if (dados.length === 0) {
            this.dom.tbody.innerHTML = `<tr><td colspan="7">${this.estadoVazio('Nenhuma transação encontrada com esses filtros.', 'btn-nova-transacao-vazio')}</td></tr>`;
            this.dom.pagination.innerHTML = '';
            document.getElementById('btn-nova-transacao-vazio')?.addEventListener('click', () => this.abrirCriacao());
            Icons.refresh();
            return;
        }

        this.dom.tbody.innerHTML = dados.map((t) => this.linha(t)).join('');
        this.paginacao(pagina, totalPaginas);
        Icons.refresh();

        this.dom.tbody.querySelectorAll('[data-editar]').forEach((btn) => {
            btn.addEventListener('click', () => this.abrirEdicao(dados.find((t) => t.id == btn.dataset.editar)));
        });
        this.dom.tbody.querySelectorAll('[data-excluir]').forEach((btn) => {
            btn.addEventListener('click', () => this.excluir(btn.dataset.excluir));
        });
    },

    linha(t) {
        const positivo = t.direcao === 'entrada';
        return `
            <tr class="border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50">
                <td class="py-3 px-4 text-slate-500 dark:text-slate-400" data-label="Data">${this.dataBr(t.data)}</td>
                <td class="py-3 px-4">
                    <div class="font-medium text-slate-900 dark:text-white">${this.escapar(t.descricao)}</div>
                    ${t.parcela_total ? `<div class="text-xs text-slate-400">${t.parcela_atual}/${t.parcela_total}</div>` : ''}
                </td>
                <td class="py-3 px-4 text-slate-500 dark:text-slate-400">${t.categoria_nome ? this.escapar(t.categoria_nome) : '—'}</td>
                <td class="py-3 px-4 text-slate-500 dark:text-slate-400">${this.escapar(t.conta_nome || '')}</td>
                <td class="py-3 px-4"><span class="badge badge-${t.status}">${this.rotuloStatus(t.status)}</span></td>
                <td class="py-3 px-4 text-right font-semibold ${positivo ? 'text-emerald-600' : 'text-rose-600'}">${positivo ? '+' : '-'} ${this.brl(t.valor)}</td>
                <td class="py-3 px-4 text-right">
                    <button data-editar="${t.id}" class="p-1.5 text-slate-400 hover:text-brand-600" aria-label="Editar"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                    <button data-excluir="${t.id}" class="p-1.5 text-slate-400 hover:text-rose-600" aria-label="Excluir"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                </td>
            </tr>`;
    },

    linhaEstado(mensagem, carregando) {
        return `<tr><td colspan="7" class="py-10 text-center text-sm text-slate-400">
            ${carregando ? '<span class="spinner inline-block mr-2 align-middle"></span>' : ''}${this.escapar(mensagem)}
        </td></tr>`;
    },

    estadoVazio(mensagem, botaoId) {
        return `<div class="py-10 text-center text-sm text-slate-400">
            <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2"></i><br>${this.escapar(mensagem)}<br>
            <button id="${botaoId}" class="mt-3 inline-flex items-center gap-2 text-brand-600 dark:text-brand-400 font-medium text-sm"><i data-lucide="plus" class="w-4 h-4"></i> Nova transação</button>
        </div>`;
    },

    paginacao(pagina, totalPaginas) {
        if (totalPaginas <= 1) { this.dom.pagination.innerHTML = ''; return; }
        let html = '';
        for (let i = 1; i <= totalPaginas; i++) {
            html += `<button data-page="${i}" class="px-3 py-1.5 text-sm rounded-lg ${i === pagina ? 'bg-brand-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'}">${i}</button>`;
        }
        this.dom.pagination.innerHTML = html;
        this.dom.pagination.querySelectorAll('[data-page]').forEach((btn) => {
            btn.addEventListener('click', () => { this.state.page = Number(btn.dataset.page); this.carregarTransacoes(); });
        });
    },

    selecionarTipo(tipo) {
        this.dom.tipoInput.value = tipo;
        document.querySelectorAll('.tipo-btn').forEach((btn) => {
            const ativo = btn.dataset.tipo === tipo;
            btn.classList.toggle('bg-brand-600', ativo);
            btn.classList.toggle('text-white', ativo);
            btn.classList.toggle('border-brand-600', ativo);
            btn.classList.toggle('border-slate-300', !ativo);
            btn.classList.toggle('dark:border-slate-700', !ativo);
            btn.classList.toggle('text-slate-600', !ativo);
            btn.classList.toggle('dark:text-slate-300', !ativo);
        });

        const transferencia = tipo === 'transferencia';
        this.dom.campoContaDestino.classList.toggle('hidden', !transferencia);
        this.dom.contaDestinoSelect.required = transferencia;
        this.dom.campoCategoria.classList.toggle('hidden', transferencia);
        this.dom.camposRecParcela.classList.toggle('hidden', transferencia);

        Array.from(this.dom.categoriaSelect.options).forEach((opt) => {
            if (!opt.value) return;
            opt.hidden = opt.dataset.tipo && opt.dataset.tipo !== tipo;
        });
    },

    abrirCriacao() {
        this.dom.form.reset();
        this.edicaoId = null;
        document.getElementById('transacao-id').value = '';
        this.dom.modalTitle.textContent = 'Nova transação';
        this.dom.camposStatusVenc.classList.remove('hidden');
        this.dom.blocoAnexos.classList.add('hidden');
        document.getElementById('transacao-data').value = new Date().toISOString().slice(0, 10);
        this.selecionarTipo('despesa');
        Modal.open('transacao-modal', '#transacao-descricao');
    },

    async abrirEdicao(resumo) {
        if (!resumo) return;
        try {
            const t = await Api.get(`api/transactions.php?id=${resumo.id}`);
            this.dom.form.reset();
            this.edicaoId = t.id;
            document.getElementById('transacao-id').value = t.id;
            document.getElementById('transacao-descricao').value = t.descricao;
            document.getElementById('transacao-valor').value = Number(t.valor).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d)(?=,))/g, '.');
            document.getElementById('transacao-data').value = t.data;
            document.getElementById('transacao-vencimento').value = t.vencimento || '';
            document.getElementById('transacao-status').value = t.status;
            document.getElementById('transacao-observacao').value = t.observacao || '';
            this.dom.contaSelect.value = t.account_id;
            this.dom.categoriaSelect.value = t.category_id || '';
            this.dom.modalTitle.textContent = 'Editar transação';
            this.selecionarTipo(t.tipo === 'transferencia' ? 'despesa' : t.tipo);
            this.dom.camposRecParcela.classList.add('hidden');
            this.dom.blocoAnexos.classList.remove('hidden');
            this.renderizarAnexos(t.anexos || []);
            Modal.open('transacao-modal', '#transacao-descricao');
        } catch (erro) {
            Toast.error(erro.message);
        }
    },

    renderizarAnexos(anexos) {
        if (anexos.length === 0) {
            this.dom.anexosLista.innerHTML = '<p class="text-xs text-slate-400">Nenhum anexo ainda.</p>';
            return;
        }
        this.dom.anexosLista.innerHTML = anexos.map((a) => `
            <div class="flex items-center justify-between text-sm bg-slate-50 dark:bg-slate-800 rounded-lg px-3 py-2">
                <a href="download.php?id=${a.id}" target="_blank" class="flex items-center gap-2 text-brand-600 dark:text-brand-400 truncate hover:underline">
                    <i data-lucide="file" class="w-4 h-4 shrink-0"></i>${this.escapar(a.nome_original)}
                </a>
                <button data-excluir-anexo="${a.id}" class="text-slate-400 hover:text-rose-600 shrink-0"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>`).join('');
        Icons.refresh();

        this.dom.anexosLista.querySelectorAll('[data-excluir-anexo]').forEach((btn) => {
            btn.addEventListener('click', () => this.excluirAnexo(btn.dataset.excluirAnexo));
        });
    },

    async enviarAnexo() {
        const arquivo = this.dom.anexoInput.files[0];
        if (!arquivo || !this.edicaoId) return;

        const formData = new FormData();
        formData.append('anexo', arquivo);
        formData.append('transaction_id', String(this.edicaoId));
        formData.append('csrf_token', window.csrfToken);

        try {
            await Api.upload('api/uploads.php', formData);
            Toast.success('Anexo adicionado.');
            const t = await Api.get(`api/transactions.php?id=${this.edicaoId}`);
            this.renderizarAnexos(t.anexos || []);
        } catch (erro) {
            Toast.error(erro.message);
        } finally {
            this.dom.anexoInput.value = '';
        }
    },

    async excluirAnexo(id) {
        const ok = await ConfirmModal.ask('Excluir anexo?', 'Essa ação não poderá ser desfeita.');
        if (!ok) return;

        try {
            await Api.remove(`api/uploads.php?id=${id}`, { csrf_token: window.csrfToken });
            Toast.success('Anexo excluído.');
            const t = await Api.get(`api/transactions.php?id=${this.edicaoId}`);
            this.renderizarAnexos(t.anexos || []);
        } catch (erro) {
            Toast.error(erro.message);
        }
    },

    async salvar(event) {
        event.preventDefault();
        const botao = document.getElementById('transacao-submit');
        botao.disabled = true;

        const dados = Object.fromEntries(new FormData(this.dom.form).entries());
        dados.valor = this.desmascarar(dados.valor);

        try {
            if (this.edicaoId) {
                await Api.put(`api/transactions.php?id=${this.edicaoId}`, dados);
                Toast.success('Transação atualizada.');
            } else {
                await Api.post('api/transactions.php', dados);
                Toast.success('Transação criada.');
            }
            Modal.close('transacao-modal');
            await this.carregarTransacoes();
        } catch (erro) {
            Toast.error(erro.message);
        } finally {
            botao.disabled = false;
        }
    },

    async excluir(id) {
        const ok = await ConfirmModal.ask('Excluir transação?', 'Essa ação não poderá ser desfeita.');
        if (!ok) return;

        try {
            await Api.remove(`api/transactions.php?id=${id}`, { csrf_token: window.csrfToken });
            Toast.success('Transação excluída.');
            await this.carregarTransacoes();
        } catch (erro) {
            Toast.error(erro.message);
        }
    },

    rotuloStatus(s) { return { pendente: 'Pendente', pago: 'Pago', cancelado: 'Cancelado' }[s] || s; },
    brl(v) { return 'R$ ' + Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
    dataBr(iso) { const [a, m, d] = iso.split('-'); return `${d}/${m}/${a}`; },
    desmascarar(valor) { return valor ? valor.replace(/\./g, '').replace(',', '.') : ''; },
    escapar(texto) { const div = document.createElement('div'); div.textContent = texto ?? ''; return div.innerHTML; },
};

document.addEventListener('DOMContentLoaded', () => TransactionsPage.init());
