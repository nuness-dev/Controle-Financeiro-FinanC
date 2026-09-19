const Api = {
    async request(url, options = {}) {
        let response;

        try {
            response = await fetch(url, options);
        } catch {
            throw new Error('Falha de comunicação com o servidor.');
        }

        let payload = null;
        try {
            payload = await response.json();
        } catch {
            // resposta sem corpo JSON (ex.: CSV) — quem chamou trata separadamente
        }

        if (!response.ok || !payload || payload.success === false) {
            throw new Error(payload?.message || 'Ocorreu um erro inesperado.');
        }

        return payload.data ?? null;
    },

    json(url, method, body) {
        return this.request(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body ?? {}),
        });
    },

    get(url) {
        return this.request(url, { method: 'GET' });
    },

    post(url, body) {
        return this.json(url, 'POST', body);
    },

    put(url, body) {
        return this.json(url, 'PUT', body);
    },

    patch(url, body) {
        return this.json(url, 'PATCH', body);
    },

    remove(url, body) {
        return this.json(url, 'DELETE', body);
    },

    upload(url, formData) {
        return this.request(url, { method: 'POST', body: formData });
    },
};
