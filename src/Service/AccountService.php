<?php

declare(strict_types=1);

namespace App\Service;

use App\Helpers\Money;
use App\Helpers\Validator;
use App\Repository\AccountRepository;
use InvalidArgumentException;

final class AccountService
{
    private const TIPOS_VALIDOS = ['corrente', 'poupanca', 'carteira', 'digital', 'investimento', 'outra'];

    private AccountRepository $contas;

    public function __construct(?AccountRepository $contas = null)
    {
        $this->contas = $contas ?? new AccountRepository();
    }

    public function listar(int $userId): array
    {
        $contas = $this->contas->listarPorUsuario($userId);

        foreach ($contas as &$conta) {
            $conta['saldo'] = $this->contas->calcularSaldo((int) $conta['id'], $userId);
        }

        return $contas;
    }

    public function obter(int $id, int $userId): array
    {
        $conta = $this->contas->buscarPorIdEUsuario($id, $userId);

        if ($conta === null) {
            throw new InvalidArgumentException('Conta não encontrada.', 404);
        }

        $conta['saldo'] = $this->contas->calcularSaldo($id, $userId);

        return $conta;
    }

    public function criar(int $userId, array $dados): int
    {
        return $this->contas->criar($userId, $this->validar($dados));
    }

    public function atualizar(int $id, int $userId, array $dados): array
    {
        $validado = $this->validar($dados);
        $validado['ativa'] = !empty($dados['ativa']) ? 1 : 0;

        if (!$this->contas->atualizar($id, $userId, $validado)) {
            throw new InvalidArgumentException('Conta não encontrada.', 404);
        }

        return $this->obter($id, $userId);
    }

    public function excluir(int $id, int $userId): void
    {
        $this->obter($id, $userId);

        if ($this->contas->temMovimentacao($id, $userId)) {
            throw new InvalidArgumentException('Não é possível excluir uma conta com movimentações. Desative-a em vez de excluir.');
        }

        $this->contas->excluir($id, $userId);
    }

    public function saldoTotal(int $userId): array
    {
        return $this->contas->calcularSaldoTotal($userId);
    }

    private function validar(array $dados): array
    {
        $nome = trim((string) ($dados['nome'] ?? ''));
        $tipo = (string) ($dados['tipo'] ?? 'corrente');
        $cor = (string) ($dados['cor'] ?? '#6366f1');

        if ($nome === '') {
            throw new InvalidArgumentException('Nome da conta é obrigatório.');
        }

        if (!Validator::dentroDe($tipo, self::TIPOS_VALIDOS)) {
            throw new InvalidArgumentException('Tipo de conta inválido.');
        }

        if (!Validator::corHexValida($cor)) {
            throw new InvalidArgumentException('Cor inválida.');
        }

        $saldoInicial = isset($dados['saldo_inicial']) ? Money::normalizarEntrada((string) $dados['saldo_inicial']) : '0.00';

        return [
            'nome' => $nome,
            'tipo' => $tipo,
            'saldo_inicial' => $saldoInicial,
            'instituicao' => !empty($dados['instituicao']) ? trim((string) $dados['instituicao']) : null,
            'cor' => $cor,
        ];
    }
}
