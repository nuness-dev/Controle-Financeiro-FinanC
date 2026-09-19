const Icons = {
    refresh() {
        if (window.lucide) {
            window.lucide.createIcons();
        }
    },
};

document.addEventListener('DOMContentLoaded', () => {
    Icons.refresh();

    document.getElementById('theme-toggle')?.addEventListener('click', () => {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('financ-theme', isDark ? 'dark' : 'light');
        document.dispatchEvent(new CustomEvent('financ:theme-changed'));
    });

    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
        sidebar?.classList.remove('-translate-x-full');
        overlay?.classList.remove('hidden');
    });

    overlay?.addEventListener('click', () => {
        sidebar?.classList.add('-translate-x-full');
        overlay?.classList.add('hidden');
    });

    const collapseBtn = document.getElementById('sidebar-collapse');
    if (collapseBtn && sidebar) {
        const salvo = localStorage.getItem('financ-sidebar-collapsed') === 'true';
        sidebar.dataset.collapsed = salvo ? 'true' : 'false';

        collapseBtn.addEventListener('click', () => {
            const colapsada = sidebar.dataset.collapsed === 'true';
            sidebar.dataset.collapsed = colapsada ? 'false' : 'true';
            localStorage.setItem('financ-sidebar-collapsed', String(!colapsada));
        });
    }

    document.getElementById('logout-button')?.addEventListener('click', async () => {
        try {
            await Api.post('api/auth.php?action=logout', {});
        } finally {
            window.location.href = 'login.php';
        }
    });
});
