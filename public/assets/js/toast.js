const Toast = {
    show(mensagem, tipo = 'success') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const icone = tipo === 'success' ? 'check-circle-2' : 'alert-circle';
        const el = document.createElement('div');
        el.className = `toast toast-${tipo} pointer-events-auto`;
        el.setAttribute('role', 'status');

        const icon = document.createElement('i');
        icon.dataset.lucide = icone;
        icon.className = 'w-[18px] h-[18px] shrink-0 mt-0.5';

        const texto = document.createElement('span');
        texto.textContent = mensagem;

        el.append(icon, texto);
        container.appendChild(el);
        Icons.refresh();

        setTimeout(() => el.remove(), 4500);
    },

    success(mensagem) {
        this.show(mensagem, 'success');
    },

    error(mensagem) {
        this.show(mensagem, 'error');
    },
};
