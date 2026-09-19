<div id="sidebar-overlay" class="fixed inset-0 bg-slate-900/50 z-30 hidden lg:hidden"></div>

<aside id="sidebar" class="w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col fixed inset-y-0 left-0 z-40 -translate-x-full lg:translate-x-0 lg:relative transition-all duration-200 data-[collapsed=true]:lg:w-[76px]" data-collapsed="false">
    <div class="h-16 flex items-center gap-2 px-5 border-b border-slate-200 dark:border-slate-800 overflow-hidden">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 shrink-0">
            <line x1="12" y1="2" x2="12" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
        </svg>
        <span id="sidebar-brand" class="text-lg font-bold text-slate-900 dark:text-white tracking-tight whitespace-nowrap">FinanC</span>
    </div>

    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto overflow-x-hidden">
        <?php
        $itens = [
            ['dashboard.php', 'layout-dashboard', 'Dashboard', 'dashboard'],
            ['transacoes.php', 'arrow-left-right', 'Transações', 'transacoes'],
            ['contas.php', 'wallet', 'Contas', 'contas'],
            ['cartoes.php', 'credit-card', 'Cartões', 'cartoes'],
            ['categorias.php', 'tag', 'Categorias', 'categorias'],
            ['metas.php', 'target', 'Metas', 'metas'],
            ['relatorios.php', 'bar-chart-3', 'Relatórios', 'relatorios'],
        ];
        foreach ($itens as [$href, $icone, $label, $chave]):
            $ativo = ($active ?? '') === $chave;
        ?>
        <a href="<?= $href ?>" title="<?= htmlspecialchars($label) ?>"
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap <?= $ativo ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
            <i data-lucide="<?= $icone ?>" class="w-[18px] h-[18px] shrink-0"></i>
            <span class="sidebar-label"><?= htmlspecialchars($label) ?></span>
        </a>
        <?php endforeach; ?>

        <?php if (($isAdmin ?? false)): ?>
        <a href="admin.php" title="Administração"
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap <?= ($active ?? '') === 'admin' ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
            <i data-lucide="shield" class="w-[18px] h-[18px] shrink-0"></i>
            <span class="sidebar-label">Administração</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="p-3 border-t border-slate-200 dark:border-slate-800 space-y-1">
        <button id="sidebar-collapse" class="hidden lg:flex w-full items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
            <i data-lucide="panel-left-close" class="w-[18px] h-[18px] shrink-0"></i>
            <span class="sidebar-label">Recolher</span>
        </button>
        <a href="perfil.php" title="Perfil"
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap <?= ($active ?? '') === 'perfil' ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
            <i data-lucide="user" class="w-[18px] h-[18px] shrink-0"></i>
            <span class="sidebar-label">Perfil</span>
        </a>
        <button id="logout-button" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition-colors">
            <i data-lucide="log-out" class="w-[18px] h-[18px] shrink-0"></i>
            <span class="sidebar-label">Sair</span>
        </button>
    </div>
</aside>
