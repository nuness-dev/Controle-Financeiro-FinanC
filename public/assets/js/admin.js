function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[char]));
}

async function carregarUsuarios() {
    const tbody = document.getElementById('admin-tbody');
    tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">Carregando...</td></tr>';

    try {
        const data = await Api.get('api/users.php?action=admin-listar');
        document.getElementById('admin-usuarios-ativos').textContent = data.usuarios_ativos;

        if (!data.usuarios.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">Nenhum usuário encontrado.</td></tr>';
            return;
        }

        tbody.innerHTML = data.usuarios.map((usuario) => {
            const ativo = Number(usuario.ativo) === 1;
            const ehAtual = Number(usuario.id) === Number(window.usuarioAtualId);
            return `
                <tr class="border-b border-slate-100 dark:border-slate-800 last:border-0">
                    <td class="py-3 px-4">
                        <div class="font-medium text-slate-900 dark:text-white">${escapeHtml(usuario.nome)}</div>
                        <div class="text-xs text-slate-500">${escapeHtml(usuario.email)}</div>
                    </td>
                    <td class="py-3 px-4">
                        <select data-admin-perfil="${usuario.id}" ${ehAtual ? 'disabled' : ''} class="rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-2 py-1 text-sm">
                            <option value="usuario" ${usuario.perfil === 'usuario' ? 'selected' : ''}>Usuário</option>
                            <option value="admin" ${usuario.perfil === 'admin' ? 'selected' : ''}>Admin</option>
                        </select>
                    </td>
                    <td class="py-3 px-4"><span class="badge ${ativo ? 'badge-pago' : 'badge-cancelado'}">${ativo ? 'Ativo' : 'Inativo'}</span></td>
                    <td class="py-3 px-4 text-slate-500">${escapeHtml(usuario.ultimo_acesso || '-')}</td>
                    <td class="py-3 px-4 text-right">
                        <button data-admin-status="${usuario.id}" data-ativo="${ativo ? '1' : '0'}" ${ehAtual ? 'disabled' : ''} class="inline-flex items-center justify-center gap-2 text-sm font-semibold px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-50">
                            <i data-lucide="${ativo ? 'user-x' : 'user-check'}" class="w-4 h-4"></i> ${ativo ? 'Desativar' : 'Ativar'}
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
        Icons.refresh();
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-6 text-center text-rose-500">Não foi possível carregar os usuários.</td></tr>';
        Toast.error(error.message);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    carregarUsuarios();
    document.getElementById('admin-recarregar')?.addEventListener('click', carregarUsuarios);

    document.addEventListener('click', async (event) => {
        const botao = event.target.closest('[data-admin-status]');
        if (!botao) return;

        const id = botao.dataset.adminStatus;
        const ativo = botao.dataset.ativo !== '1';

        try {
            await Api.post(`api/users.php?action=admin-status&id=${id}`, {
                csrf_token: window.csrfToken,
                ativo,
            });
            Toast.success('Status atualizado.');
            carregarUsuarios();
        } catch (error) {
            Toast.error(error.message);
        }
    });

    document.addEventListener('change', async (event) => {
        const select = event.target.closest('[data-admin-perfil]');
        if (!select) return;

        try {
            await Api.post(`api/users.php?action=admin-perfil&id=${select.dataset.adminPerfil}`, {
                csrf_token: window.csrfToken,
                perfil: select.value,
            });
            Toast.success('Perfil atualizado.');
            carregarUsuarios();
        } catch (error) {
            Toast.error(error.message);
            carregarUsuarios();
        }
    });
});
