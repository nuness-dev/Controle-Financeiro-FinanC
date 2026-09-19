<?php

declare(strict_types=1);

namespace App\Service;

use App\Helpers\Validator;
use App\Repository\PasswordResetRepository;
use App\Repository\UserRepository;
use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;

final class AuthService
{
    private const TOKEN_TTL_MINUTOS = 30;

    private UserRepository $usuarios;
    private PasswordResetRepository $resets;

    public function __construct(?UserRepository $usuarios = null, ?PasswordResetRepository $resets = null)
    {
        $this->usuarios = $usuarios ?? new UserRepository();
        $this->resets = $resets ?? new PasswordResetRepository();
    }

    public function registrar(string $nome, string $email, string $senha, string $confirmarSenha): array
    {
        $nome = trim($nome);
        $email = trim(strtolower($email));

        if ($nome === '' || $email === '') {
            throw new InvalidArgumentException('Nome e email são obrigatórios.');
        }

        if (!Validator::emailValido($email)) {
            throw new InvalidArgumentException('Email inválido.');
        }

        if (!Validator::senhaForte($senha)) {
            throw new InvalidArgumentException('A senha precisa ter pelo menos 8 caracteres.');
        }

        if ($senha !== $confirmarSenha) {
            throw new InvalidArgumentException('As senhas não coincidem.');
        }

        if ($this->usuarios->buscarPorEmail($email) !== null) {
            throw new InvalidArgumentException('Já existe uma conta com esse email.');
        }

        $id = $this->usuarios->criar($nome, $email, password_hash($senha, PASSWORD_DEFAULT));

        return ['id' => $id, 'nome' => $nome, 'perfil' => 'usuario'];
    }

    public function autenticar(string $email, string $senha): array
    {
        $usuario = $this->usuarios->buscarPorEmail(trim(strtolower($email)));

        if ($usuario === null || !$usuario['ativo'] || !password_verify($senha, $usuario['senha_hash'])) {
            throw new InvalidArgumentException('Email ou senha incorretos.');
        }

        $this->usuarios->registrarAcesso((int) $usuario['id']);

        return $usuario;
    }

    public function solicitarRedefinicao(string $email): ?string
    {
        $usuario = $this->usuarios->buscarPorEmail(trim(strtolower($email)));

        if ($usuario === null) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiraEm = (new DateTimeImmutable())
            ->add(new DateInterval('PT' . self::TOKEN_TTL_MINUTOS . 'M'))
            ->format('Y-m-d H:i:s');

        $this->resets->criar((int) $usuario['id'], $tokenHash, $expiraEm);

        return $token;
    }

    public function redefinirSenha(string $token, string $novaSenha, string $confirmarSenha): void
    {
        if (!Validator::senhaForte($novaSenha)) {
            throw new InvalidArgumentException('A senha precisa ter pelo menos 8 caracteres.');
        }

        if ($novaSenha !== $confirmarSenha) {
            throw new InvalidArgumentException('As senhas não coincidem.');
        }

        $registro = $this->resets->buscarValidoPorTokenHash(hash('sha256', $token));

        if ($registro === null) {
            throw new InvalidArgumentException('Link de redefinição inválido ou expirado.');
        }

        $this->usuarios->atualizarSenha((int) $registro['user_id'], password_hash($novaSenha, PASSWORD_DEFAULT));
        $this->resets->excluirPorUsuario((int) $registro['user_id']);
    }
}
