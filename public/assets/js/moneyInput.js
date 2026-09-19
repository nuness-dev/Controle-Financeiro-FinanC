const MoneyInput = {
    formatar(digitos) {
        const centavos = digitos.replace(/\D/g, '').replace(/^0+(?=\d)/, '') || '0';
        const valor = (parseInt(centavos, 10) / 100).toFixed(2);
        const [inteiro, decimal] = valor.split('.');
        const inteiroFormatado = inteiro.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

        return `${inteiroFormatado},${decimal}`;
    },

    aplicar(el) {
        el.addEventListener('input', () => {
            const posicaoFinal = el.value.length;
            el.value = this.formatar(el.value);
            el.setSelectionRange(el.value.length, el.value.length);
        });

        el.addEventListener('focus', () => {
            if (!el.value) el.value = '0,00';
        });
    },

    aplicarEm(container = document) {
        container.querySelectorAll('[data-money-input]').forEach((el) => {
            if (el.dataset.moneyBound) return;
            el.dataset.moneyBound = 'true';
            this.aplicar(el);
        });
    },
};

document.addEventListener('DOMContentLoaded', () => MoneyInput.aplicarEm());
