const ReportsPage = {
    charts: {},

    init() {
        document.getElementById('filtro-periodo').addEventListener('change', (e) => {
            document.getElementById('periodo-customizado').classList.toggle('hidden', e.target.value !== 'personalizado');
        });
        document.getElementById('periodo-aplicar').addEventListener('click', () => this.carregar());
        document.getElementById('btn-exportar-csv').addEventListener('click', (e) => {
            e.preventDefault();
            window.location.href = `api/reports.php?${this.parametros('csv').toString()}`;
        });
        document.addEventListener('financ:theme-changed', () => this.carregar());

        this.carregar();
    },

    parametros(formato = 'json') {
        const periodo = document.getElementById('filtro-periodo').value;
        const params = new URLSearchParams({ periodo, formato });
        if (periodo === 'personalizado') {
            params.set('data_inicio', document.getElementById('periodo-inicio').value);
            params.set('data_fim', document.getElementById('periodo-fim').value);
        }
        return params;
    },

    async carregar() {
        try {
            const r = await Api.get(`api/reports.php?${this.parametros().toString()}`);
            const resultado = Number(r.receitas) - Number(r.despesas);

            document.getElementById('rel-receitas').textContent = this.brl(r.receitas);
            document.getElementById('rel-despesas').textContent = this.brl(r.despesas);
            const el = document.getElementById('rel-resultado');
            el.textContent = this.brl(resultado);
            el.className = `text-xl font-bold mt-1 ${resultado < 0 ? 'text-rose-600' : 'text-emerald-600'}`;
            document.getElementById('rel-saldo-previsto').textContent = this.brl(r.saldo_previsto);

            this.grafico('rel-grafico-categoria', 'doughnut', {
                labels: r.gastos_por_categoria.map((g) => g.categoria),
                datasets: [{ data: r.gastos_por_categoria.map((g) => Number(g.total)), backgroundColor: r.gastos_por_categoria.map((g) => g.cor) }],
            });
            this.grafico('rel-grafico-fluxo', 'line', {
                labels: r.evolucao.map((e) => e.data.slice(8, 10) + '/' + e.data.slice(5, 7)),
                datasets: [
                    { label: 'Receitas', data: r.evolucao.map((e) => Number(e.receitas)), borderColor: '#10b981', tension: 0.3 },
                    { label: 'Despesas', data: r.evolucao.map((e) => Number(e.despesas)), borderColor: '#f43f5e', tension: 0.3 },
                ],
            });
        } catch (erro) {
            Toast.error(erro.message);
        }
    },

    grafico(id, tipo, data) {
        const escuro = document.documentElement.classList.contains('dark');
        const grid = escuro ? '#334155' : '#e2e8f0';
        const texto = escuro ? '#cbd5e1' : '#475569';
        this.charts[id]?.destroy();
        this.charts[id] = new Chart(document.getElementById(id), {
            type: tipo,
            data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: tipo === 'doughnut' ? {} : { x: { grid: { color: grid }, ticks: { color: texto } }, y: { grid: { color: grid }, ticks: { color: texto } } },
                plugins: { legend: { labels: { color: texto } } },
            },
        });
    },

    brl(v) { return 'R$ ' + Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
};

document.addEventListener('DOMContentLoaded', () => ReportsPage.init());
