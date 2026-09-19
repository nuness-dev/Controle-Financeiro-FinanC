const Modal = {
    ultimoElementoFocado: null,

    open(id, focarSeletor = null) {
        const modal = document.getElementById(id);
        if (!modal) return;

        this.ultimoElementoFocado = document.activeElement;
        modal.classList.remove('hidden');
        Icons.refresh();

        const alvo = focarSeletor ? modal.querySelector(focarSeletor) : modal.querySelector('input, select, textarea, button');
        alvo?.focus();
    },

    close(id) {
        const modal = document.getElementById(id);
        modal?.classList.add('hidden');

        if (this.ultimoElementoFocado instanceof HTMLElement) {
            this.ultimoElementoFocado.focus();
        }
    },
};

const ConfirmModal = {
    resolver: null,

    ask(titulo, mensagem) {
        document.getElementById('confirm-modal-title').textContent = titulo;
        document.getElementById('confirm-modal-message').textContent = mensagem;
        Modal.open('confirm-modal', '#confirm-modal-ok');

        return new Promise((resolve) => {
            this.resolver = resolve;
        });
    },

    responder(valor) {
        Modal.close('confirm-modal');
        this.resolver?.(valor);
        this.resolver = null;
    },
};

document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', (event) => {
        const dismiss = event.target.closest('[data-modal-dismiss]');
        if (dismiss) {
            const modal = dismiss.closest('.fixed.inset-0.z-50, .fixed.inset-0.z-\\[70\\]');
            if (modal?.id) Modal.close(modal.id);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;

        document.querySelectorAll('.fixed.inset-0:not(.hidden)').forEach((modal) => {
            if (modal.id) Modal.close(modal.id);
        });
    });

    document.getElementById('confirm-modal-cancel')?.addEventListener('click', () => ConfirmModal.responder(false));
    document.getElementById('confirm-modal-ok')?.addEventListener('click', () => ConfirmModal.responder(true));
});
