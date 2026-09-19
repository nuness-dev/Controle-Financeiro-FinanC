<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\Validator;
use App\Repository\UserRepository;
use App\Service\UploadService;
use InvalidArgumentException;
use RuntimeException;

final class UserController
{
    private UserRepository $usuarios;

    public function __construct(?UserRepository $usuarios = null)
    {
        $this->usuarios = $usuarios ?? new UserRepository();
    }

    public function atualizarPerfil(Request $request): void
    {
        Csrf::requireValid((string) $request->input('csrf_token'));

        $nome = trim((string) $request->input('nome', ''));
        $email = trim(strtolower((string) $request->input('email', '')));

        if ($nome === '' || !Validator::emailValido($email)) {
            Response::error('Nome e email válidos são obrigatórios.', 422);
        }

        $existente = $this->usuarios->buscarPorEmail($email);
        if ($existente !== null && (int) $existente['id'] !== Auth::id()) {
            Response::error('Esse email já está em uso por outra conta.', 422);
        }

        try {
            $this->usuarios->atualizarPerfil(Auth::id(), $nome, $email);
            Auth::atualizarSessao($nome, Auth::foto());
            Response::success(['nome' => $nome, 'email' => $email]);
        } catch (RuntimeException $e) {
            Response::error('Não foi possível atualizar o perfil.', 500);
        }
    }

    public function atualizarSenha(Request $request): void
    {
        Csrf::requireValid((string) $request->input('csrf_token'));

        $senhaAtual = (string) $request->input('senha_atual', '');
        $novaSenha = (string) $request->input('nova_senha', '');
        $confirmar = (string) $request->input('confirmar_senha', '');

        $usuario = $this->usuarios->buscarPorId(Auth::id());

        if (!$usuario || !password_verify($senhaAtual, $usuario['senha_hash'])) {
            Response::error('Senha atual incorreta.', 422);
        }

        if (!Validator::senhaForte($novaSenha)) {
            Response::error('A nova senha precisa ter pelo menos 8 caracteres.', 422);
        }

        if ($novaSenha !== $confirmar) {
            Response::error('As senhas não coincidem.', 422);
        }

        try {
            $this->usuarios->atualizarSenha(Auth::id(), password_hash($novaSenha, PASSWORD_DEFAULT));
            Response::success(['message' => 'Senha atualizada com sucesso.']);
        } catch (RuntimeException $e) {
            Response::error('Não foi possível atualizar a senha.', 500);
        }
    }

    public function uploadFoto(): void
    {
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            Response::error('Sessão expirada ou requisição inválida.', 403);
        }

        if (empty($_FILES['foto'])) {
            Response::error('Nenhum arquivo enviado.', 400);
        }

        try {
            $usuario = $this->usuarios->buscarPorId(Auth::id());
            $uploadService = new UploadService();

            if (!empty($usuario['foto'])) {
                $uploadService->removerFotoPerfil($usuario['foto']);
            }

            $nomeArquivo = $uploadService->processarFotoPerfil($_FILES['foto']);
            $this->usuarios->atualizarFoto(Auth::id(), $nomeArquivo);
            Auth::atualizarSessao(Auth::nome(), $nomeArquivo);

            Response::success(['foto' => $nomeArquivo]);
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
        } catch (RuntimeException $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function removerFoto(Request $request): void
    {
        Csrf::requireValid((string) $request->input('csrf_token'));

        $usuario = $this->usuarios->buscarPorId(Auth::id());

        if (!empty($usuario['foto'])) {
            (new UploadService())->removerFotoPerfil($usuario['foto']);
        }

        $this->usuarios->atualizarFoto(Auth::id(), null);
        Auth::atualizarSessao(Auth::nome(), null);

        Response::success();
    }

    public function listarAdmin(): void
    {
        Response::success([
            'usuarios' => $this->usuarios->listarTodos(),
            'usuarios_ativos' => $this->usuarios->contarAtivos(),
        ]);
    }

    public function alterarStatus(Request $request, int $id): void
    {
        Csrf::requireValid((string) $request->input('csrf_token'));

        if ($id === Auth::id()) {
            Response::error('Você não pode desativar sua própria conta.', 422);
        }

        $ativo = (bool) $request->input('ativo', true);
        $this->usuarios->alterarStatus($id, $ativo);

        Response::success();
    }

    public function alterarPerfil(Request $request, int $id): void
    {
        Csrf::requireValid((string) $request->input('csrf_token'));

        $perfil = (string) $request->input('perfil', '');
        if (!in_array($perfil, ['admin', 'usuario'], true)) {
            Response::error('Perfil inválido.', 422);
        }

        if ($id === Auth::id() && $perfil !== 'admin') {
            Response::error('Você não pode remover seu próprio acesso de administrador.', 422);
        }

        $this->usuarios->alterarPerfil($id, $perfil);

        Response::success();
    }
}
