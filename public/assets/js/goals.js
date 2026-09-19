const GoalsPage = {
    edicaoId: null,
    contribuicaoMetaId: null,

    async init() {
        this.dom = {
            lista: document.getElementById('metas-lista'),
            form: document.getElementById('meta-form'),
            title: document.getElementById('meta-modal-title'),
            contribForm: document.getElementById('contribuicao-form'),
        };

        document.getElementById('btn-nova-meta').addEventListener('click', () => this.abrirCriacao());
        this.dom.form.addEventListener('submit', (e) => this.salvar(e));
        this.dom.contribForm.addEventListener('submit', (e) => this.contribuir(e));

        await this.carregar();
    },

    async carregar() {
        this.dom.lista.innerHTML = `<div class="col-span-full py-10 text-center text-sm text-slate-400"><span class="spinner inline-block mr-2 align-middle"></span>Carregando metas…</div>`;
        try {
            const { metas } = await Api.get('api/goals.php');
            this.renderizar(metas);
        } catch (erro) {
            this.dom.lista.innerHTML = `<div class="col-span-full py-10 text-center text-sm text-rose-500">${this.escapar(erro.message)}</div>`;
        }
    },

    renderizar(metas) {
        if (metas.length === 0) {
            this.dom.lista.innerHTML = `<div class="col-span-full py-10 text-center text-sm text-slate-400"><i data-lucide="target" class="w-8 h-8 mx-auto mb-2"></i><br>Nenhuma meta cadastrada ainda.<br>
                <button id="vazio-nova-meta" class="mt-3 inline-flex items-center gap-2 text-brand-600 dark:text-brand-400 font-medium text-sm"><i data-lucide="plus" class="w-4 h-4"></i> Criar primeira meta</button></div>`;
            Icons.refresh();
            document.getElementById('vazio-nova-meta')?.addEventListener('click', () => this.abrirCriacao());
            return;
        }

        this.dom.lista.innerHTML = metas.map((m) => `
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 cursor-pointer" data-abrir="${m.id}">
                <div class="flex items-center justify-between mb-2">
                    <p class="font-semibold text-slate-900 dark:text-white truncate">${this.escapar(m.titulo)}</p>
                    <div class="flex items-center gap-1 shrink-0">
                        <button data-editar='${this.attrJson(m)}' class="p-1 text-slate-400 hover:text-brand-600" aria-label="Editar"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                        <button data-excluir="${m.id}" class="p-1 text-slate-400 hover:text-rose-600" aria-label="Excluir"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    </div>
                </div>
                ${m.concluida ? '<span class="badge badge-pago mb-2">Meta concluída</span>' : ''}
                <div class="progress-bar mb-2"><div style="width:${m.percentual}%;background:${m.cor}"></div></div>
                <p class="text-sm text-slate-500 dark:text-slate-400">R$ ${Number(m.valor_atual).toLocaleString('pt-BR', { minimumFractionDigits: 2 })} de R$ ${Number(m.valor_objetivo).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</p>
                ${m.prazo ? `<p class="text-xs text-slate-400 mt-1">Prazo: ${this.dataBr(m.prazo)}</p>` : ''}
            </div>`).join('');

        Icons.refresh();
        this.dom.lista.querySelectorAll('[data-abrir]').forEach((card) => {
            card.addEventListener('click', (e) => {
                if (e.target.closest('[data-editar], [data-excluir]')) return;
                this.abrirContribuicao(Number(card.dataset.abrir));
            });
        });
        this.dom.lista.querySelectorAll('[data-editar]').forEach((btn) => btn.addEventListener('click', (e) => { e.stopPropagation(); this.abrirEdicao(JSON.parse(btn.dataset.editar)); }));
        this.dom.lista.querySelectorAll('[data-excluir]').forEach((btn) => btn.addEventListener('click', (e) => { e.stopPropagation(); this.excluir(btn.dataset.excluir); }));
    },

    abrirCriacao() {
        this.dom.form.reset();
        this.edicaoId = null;
        document.getElementById('meta-id').value = '';
        this.dom.title.textContent = 'Nova meta';
        Modal.open('meta-modal', '#meta-titulo');
    },

    abrirEdicao(m) {
        this.dom.form.reset();
        this.edicaoId = m.id;
        document.getElementById('meta-id').value = m.id;
        document.getElementById('meta-titulo').value = m.titulo;
        document.getElementById('meta-valor').value = Number(m.valor_objetivo).toFixed(2).replace('.', ',');
        document.getElementById('meta-prazo').value = m.prazo || '';
        document.getElementById('meta-cor').value = m.cor;
        this.dom.title.textContent = 'Editar meta';
        Modal.open('meta-modal', '#meta-titulo');
    },

    async salvar(event) {
        event.preventDefault();
        const botao = document.getElementById('meta-submit');
        botao.disabled = true;
        const dados = Object.fromEntries(new FormData(this.dom.form).entries());
        dados.valor_objetivo = dados.valor_objetivo.replace(/\./g, '').replace(',', '.');

        try {
            if (this.edicaoId) {
                await Api.put(`api/goals.php?id=${this.edicaoId}`, dados);
                Toast.success('Meta atualizada.');
            } else {
                await Api.post('api/goals.php', dados);
                Toast.success('Meta criada.');
            }
            Modal.close('meta-modal');
            await this.carregar();
        } catch (erro) {
            Toast.error(erro.message);
        } finally {
            botao.disabled = false;
        }
    },

    async excluir(id) {
        const ok = await ConfirmModal.ask('Excluir meta?', 'Essa ação não poderá ser desfeita.');
        if (!ok) return;
        try {
            await Api.remove(`api/goals.php?id=${id}`, { csrf_token: window.csrfToken });
            Toast.success('Meta excluída.');
            await this.carregar();
        } catch (erro) {
            Toast.error(erro.message);
        }
    },

    async abrirContribuicao(id) {
        this.contribuicaoMetaId = id;
        try {
            const meta = await Api.get(`api/goals.php?id=${id}`);
            document.getElementById('contribuicao-modal-title').textContent = meta.titulo;
            document.getElementById('contribuicao-atual').textContent = `R$ ${Number(meta.valor_atual).toLocaleString('pt-BR', { minimumFractionDigits: 2 })} de R$ ${Number(meta.valor_objetivo).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;
            document.getElementById('contribuicao-percentual').textContent = `${meta.percentual}%`;
            document.getElementById('contribuicao-barra').style.width = `${meta.percentual}%`;
            document.getElementById('contribuicao-barra').style.background = meta.cor;
            this.renderizarHistorico(meta.contribuicoes || []);
            Modal.open('contribuicao-modal', '#contribuicao-valor');
        } catch (erro) {
            Toast.error(erro.message);
        }
    },

    renderizarHistorico(lista) {
        const container = document.getElementById('contribuicao-historico');
        if (lista.length === 0) {
            container.innerHTML = '<p class="text-xs text-slate-400">Nenhuma contribuição ainda.</p>';
            return;
        }
        container.innerHTML = lista.map((c) => `
            <div class="flex items-center justify-between text-sm bg-slate-50 dark:bg-slate-800 rounded-lg px-3 py-2">
                <span class="text-slate-600 dark:text-slate-300">${this.dataHoraBr(c.criado_em)}</span>
                <span class="font-semibold text-emerald-600">+ R$ ${Number(c.valor).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</span>
            </div>`).join('');
    },

    async contribuir(event) {
        event.preventDefault();
        const botao = document.getElementById('contribuicao-submit');
        botao.disabled = true;
        const dados = Object.fromEntries(new FormData(this.dom.contribForm).entries());
        dados.valor = dados.valor.replace(/\./g, '').replace(',', '.');

        try {
            await Api.post(`api/goals.php?action=contribuir&id=${this.contribuicaoMetaId}`, dados);
            Toast.success('Contribuição adicionada.');
            this.dom.contribForm.reset();
            await this.abrirContribuicao(this.contribuicaoMetaId);
            await this.carregar();
        } catch (erro) {
            Toast.error(erro.message);
        } finally {
            botao.disabled = false;
        }
    },

    dataBr(iso) { const [a, m, d] = iso.slice(0, 10).split('-'); return `${d}/${m}/${a}`; },
    dataHoraBr(dt) { const [data, hora] = dt.split(' '); return `${this.dataBr(data)} ${hora?.slice(0, 5) || ''}`; },
    attrJson(obj) { const div = document.createElement('div'); div.textContent = JSON.stringify(obj); return div.innerHTML; },
    escapar(texto) { const div = document.createElement('div'); div.textContent = texto ?? ''; return div.innerHTML; },
};

document.addEventListener('DOMContentLoaded', () => GoalsPage.init());
