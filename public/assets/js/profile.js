function setLoading(form, loading) {
    form.querySelectorAll('button, input').forEach((el) => {
        el.disabled = loading;
    });
}

function formJson(form) {
    return Object.fromEntries(new FormData(form).entries());
}

document.addEventListener('DOMContentLoaded', () => {
    const formPerfil = document.getElementById('form-perfil');
    const formSenha = document.getElementById('form-senha');
    const formFoto = document.getElementById('form-foto');
    const removerFoto = document.getElementById('btn-remover-foto');

    formPerfil?.addEventListener('submit', async (event) => {
        event.preventDefault();
        setLoading(formPerfil, true);

        try {
            await Api.post('api/users.php', formJson(formPerfil));
            Toast.success('Perfil atualizado com sucesso.');
        } catch (error) {
            Toast.error(error.message);
        } finally {
            setLoading(formPerfil, false);
        }
    });

    formSenha?.addEventListener('submit', async (event) => {
        event.preventDefault();
        setLoading(formSenha, true);

        try {
            await Api.post('api/users.php?action=senha', formJson(formSenha));
            formSenha.reset();
            Toast.success('Senha atualizada com sucesso.');
        } catch (error) {
            Toast.error(error.message);
        } finally {
            setLoading(formSenha, false);
        }
    });

    formFoto?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const input = formFoto.querySelector('input[type="file"]');
        if (!input?.files?.length) {
            Toast.error('Selecione uma imagem antes de enviar.');
            return;
        }

        setLoading(formFoto, true);
        try {
            await Api.upload('api/users.php?action=foto', new FormData(formFoto));
            const preview = document.getElementById('perfil-foto-preview');
            document.getElementById('perfil-foto-placeholder')?.classList.add('hidden');
            preview.src = `avatar.php?t=${Date.now()}`;
            preview.classList.remove('hidden');
            input.value = '';
            Toast.success('Foto atualizada.');
        } catch (error) {
            Toast.error(error.message);
        } finally {
            setLoading(formFoto, false);
        }
    });

    removerFoto?.addEventListener('click', async () => {
        const ok = await ConfirmModal.ask('Remover foto', 'Deseja remover sua foto de perfil?');
        if (!ok) return;

        try {
            await Api.post('api/users.php?action=remover-foto', { csrf_token: window.csrfToken });
            window.location.reload();
        } catch (error) {
            Toast.error(error.message);
        }
    });
});
