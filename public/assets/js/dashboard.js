const DashboardPage = {
    charts: {},
    periodo: 'este_mes',

    async init() {
        document.getElementById('filtro-periodo')?.addEventListener('change', (e) => {
            this.periodo = e.target.value;
            const custom = document.getElementById('periodo-customizado');
            custom?.classList.toggle('hidden', this.periodo !== 'personalizado');
            if (this.periodo !== 'personalizado') this.carregar();
        });

        document.getElementById('periodo-aplicar')?.addEventListener('click', () => this.carregar());

        document.addEventListener('financ:theme-changed', () => this.carregar());

        await this.carregar();
    },

    async carregar() {
        const params = new URLSearchParams({ periodo: this.periodo });

        if (this.periodo === 'personalizado') {
            const inicio = document.getElementById('periodo-inicio')?.value;
            const fim = document.getElementById('periodo-fim')?.value;
            if (!inicio || !fim) return;
            params.set('data_inicio', inicio);
            params.set('data_fim', fim);
        }

        try {
            const resumo = await Api.get(`api/reports.php?${params.toString()}`);
            this.renderCards(resumo);
            this.renderGraficoReceitaDespesa(resumo);
            this.renderGraficoCategoria(resumo.gastos_por_categoria);
            this.renderGraficoEvolucao(resumo.evolucao);
            this.renderUltimas(resumo.ultimas_transacoes);
            this.renderVencimentos(resumo.proximos_vencimentos);
        } catch (erro) {
            Toast.error(erro.message);
        }
    },

    renderCards(resumo) {
        document.getElementById('card-saldo-atual').textContent = this.brl(resumo.saldo_atual);
        document.getElementById('card-saldo-previsto').textContent = this.brl(resumo.saldo_previsto);
        document.getElementById('card-receitas').textContent = this.brl(resumo.receitas);
        document.getElementById('card-despesas').textContent = this.brl(resumo.despesas);
        document.getElementById('card-pendentes').textContent = resumo.contas_pendentes;
    },

    paleta() {
        const escuro = document.documentElement.classList.contains('dark');
        return { grid: escuro ? '#334155' : '#e2e8f0', texto: escuro ? '#cbd5e1' : '#475569' };
    },

    renderGraficoReceitaDespesa(resumo) {
        this.criarGrafico('grafico-receita-despesa', 'bar', {
            labels: ['Receitas', 'Despesas'],
            datasets: [{ data: [Number(resumo.receitas), Number(resumo.despesas)], backgroundColor: ['#10b981', '#f43f5e'] }],
        }, { plugins: { legend: { display: false } } });
    },

    renderGraficoCategoria(gastos) {
        this.criarGrafico('grafico-categoria', 'doughnut', {
            labels: gastos.map((g) => g.categoria),
            datasets: [{ data: gastos.map((g) => Number(g.total)), backgroundColor: gastos.map((g) => g.cor) }],
        });
    },

    renderGraficoEvolucao(evolucao) {
        this.criarGrafico('grafico-evolucao', 'line', {
            labels: evolucao.map((e) => this.diaCurto(e.data)),
            datasets: [
                { label: 'Receitas', data: evolucao.map((e) => Number(e.receitas)), borderColor: '#10b981', tension: 0.3 },
                { label: 'Despesas', data: evolucao.map((e) => Number(e.despesas)), borderColor: '#f43f5e', tension: 0.3 },
            ],
        });
    },

    criarGrafico(canvasId, tipo, data, extraOptions = {}) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;

        const { grid, texto } = this.paleta();
        this.charts[canvasId]?.destroy();
        this.charts[canvasId] = new Chart(canvas, {
            type: tipo,
            data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: tipo === 'doughnut' ? {} : { x: { grid: { color: grid }, ticks: { color: texto } }, y: { grid: { color: grid }, ticks: { color: texto } } },
                plugins: { legend: { labels: { color: texto } } },
                ...extraOptions,
            },
        });
    },

    renderUltimas(lista) {
        const container = document.getElementById('lista-ultimas-transacoes');
        if (!container) return;

        if (lista.length === 0) {
            container.innerHTML = this.estadoVazio('Nenhuma transação ainda.');
            return;
        }

        container.innerHTML = lista.map((t) => `
            <div class="flex items-center justify-between py-2.5 border-b border-slate-100 dark:border-slate-800 last:border-0">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-800 dark:text-slate-100 truncate">${this.escapar(t.descricao)}</p>
                    <p class="text-xs text-slate-400">${this.dataBr(t.data)}</p>
                </div>
                <span class="text-sm font-semibold ${t.direcao === 'entrada' ? 'text-emerald-600' : 'text-rose-600'}">
                    ${t.direcao === 'entrada' ? '+' : '-'} ${this.brl(t.valor)}
                </span>
            </div>
        `).join('');
        Icons.refresh();
    },

    renderVencimentos(lista) {
        const container = document.getElementById('lista-vencimentos');
        if (!container) return;

        if (lista.length === 0) {
            container.innerHTML = this.estadoVazio('Nenhum vencimento próximo.');
            return;
        }

        container.innerHTML = lista.map((t) => `
            <div class="flex items-center justify-between py-2.5 border-b border-slate-100 dark:border-slate-800 last:border-0">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-800 dark:text-slate-100 truncate">${this.escapar(t.descricao)}</p>
                    <p class="text-xs text-slate-400">${this.dataBr(t.vencimento)}</p>
                </div>
                <span class="text-sm font-semibold text-amber-600">${this.brl(t.valor)}</span>
            </div>
        `).join('');
        Icons.refresh();
    },

    estadoVazio(texto) {
        return `<div class="py-8 text-center text-sm text-slate-400"><i data-lucide="inbox" class="w-6 h-6 mx-auto mb-2"></i><br>${texto}</div>`;
    },

    brl(valor) {
        return 'R$ ' + Number(valor).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },

    dataBr(iso) {
        if (!iso) return '';
        const [ano, mes, dia] = iso.slice(0, 10).split('-');
        return `${dia}/${mes}/${ano}`;
    },

    diaCurto(iso) {
        const [, mes, dia] = iso.split('-');
        return `${dia}/${mes}`;
    },

    escapar(texto) {
        const div = document.createElement('div');
        div.textContent = texto ?? '';
        return div.innerHTML;
    },
};

document.addEventListener('DOMContentLoaded', () => DashboardPage.init());
