<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Service\AuthService;
use InvalidArgumentException;
use RuntimeException;

final class AuthController
{
    private AuthService $service;

    public function __construct(?AuthService $service = null)
    {
        $this->service = $service ?? new AuthService();
    }

    public function registrar(Request $request): void
    {
        try {
            $usuario = $this->service->registrar(
                (string) $request->input('nome', ''),
                (string) $request->input('email', ''),
                (string) $request->input('senha', ''),
                (string) $request->input('confirmar_senha', '')
            );

            Auth::login((int) $usuario['id'], $usuario['nome'], $usuario['perfil']);

            Response::success(['id' => $usuario['id'], 'nome' => $usuario['nome']], 201);
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
        } catch (RuntimeException $e) {
            Response::error('Não foi possível concluir o cadastro no momento.', 500);
        }
    }

    public function login(Request $request): void
    {
        try {
            $usuario = $this->service->autenticar(
                (string) $request->input('email', ''),
                (string) $request->input('senha', '')
            );

            Auth::login((int) $usuario['id'], $usuario['nome'], $usuario['perfil'], $usuario['foto']);

            Response::success(['id' => $usuario['id'], 'nome' => $usuario['nome']]);
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), 401);
        } catch (RuntimeException $e) {
            Response::error('Não foi possível autenticar no momento.', 500);
        }
    }

    public function logout(): void
    {
        Auth::logout();

        Response::success();
    }

    public function esqueciSenha(Request $request): void
    {
        try {
            $token = $this->service->solicitarRedefinicao((string) $request->input('email', ''));

            $dados = ['message' => 'Se esse email tiver uma conta, um link de redefinição foi gerado.'];

            if ($token !== null) {
                $dados['reset_link'] = $this->linkRedefinicao($token);
            }

            Response::success($dados);
        } catch (RuntimeException $e) {
            Response::error('Não foi possível processar a solicitação.', 500);
        }
    }

    public function redefinirSenha(Request $request): void
    {
        try {
            $this->service->redefinirSenha(
                (string) $request->input('token', ''),
                (string) $request->input('senha', ''),
                (string) $request->input('confirmar_senha', '')
            );

            Response::success(['message' => 'Senha redefinida com sucesso.']);
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
        } catch (RuntimeException $e) {
            Response::error('Não foi possível redefinir a senha no momento.', 500);
        }
    }

    private function linkRedefinicao(string $token): string
    {
        $base = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');

        return $base !== '' ? "$base/redefinir-senha.php?token=$token" : "redefinir-senha.php?token=$token";
    }
}
