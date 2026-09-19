<header class="h-16 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-4 lg:px-6 gap-3">
    <div class="flex items-center gap-3 min-w-0">
        <button id="sidebar-toggle" class="lg:hidden text-slate-500 dark:text-slate-400" aria-label="Abrir menu">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
        <h1 class="text-base font-semibold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($pageTitle ?? '') ?></h1>
    </div>

    <div class="flex items-center gap-3 shrink-0">
        <button id="theme-toggle" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" aria-label="Alternar tema">
            <i data-lucide="sun" class="w-[18px] h-[18px] hidden dark:block"></i>
            <i data-lucide="moon" class="w-[18px] h-[18px] block dark:hidden"></i>
        </button>
        <a href="perfil.php" class="flex items-center gap-2 pl-3 border-l border-slate-200 dark:border-slate-800" aria-label="Ir para o perfil">
            <?php if (!empty($userFoto)): ?>
                <img src="avatar.php" alt="" class="w-8 h-8 rounded-full object-cover">
            <?php else: ?>
                <div class="w-8 h-8 rounded-full bg-brand-600 text-white flex items-center justify-center text-xs font-bold">
                    <?= htmlspecialchars(mb_strtoupper(mb_substr($userNome ?? '?', 0, 1))) ?>
                </div>
            <?php endif; ?>
            <span class="text-sm font-medium text-slate-700 dark:text-slate-200 hidden sm:inline"><?= htmlspecialchars($userNome ?? '') ?></span>
        </a>
    </div>
</header>
