<?php

declare(strict_types=1);

namespace App\Service;

use App\Helpers\Money;
use App\Helpers\Validator;
use App\Repository\CardRepository;
use InvalidArgumentException;

final class CardService
{
    private CardRepository $cartoes;

    public function __construct(?CardRepository $cartoes = null)
    {
        $this->cartoes = $cartoes ?? new CardRepository();
    }

    public function listar(int $userId): array
    {
        return $this->cartoes->listarPorUsuario($userId);
    }

    public function obter(int $id, int $userId): array
    {
        $cartao = $this->cartoes->buscarPorIdEUsuario($id, $userId);

        if ($cartao === null) {
            throw new InvalidArgumentException('Cartão não encontrado.', 404);
        }

        return $cartao;
    }

    public function criar(int $userId, array $dados): int
    {
        return $this->cartoes->criar($userId, $this->validar($dados));
    }

    public function atualizar(int $id, int $userId, array $dados): array
    {
        $validado = $this->validar($dados);
        $validado['ativo'] = !empty($dados['ativo']) ? 1 : 0;

        if (!$this->cartoes->atualizar($id, $userId, $validado)) {
            throw new InvalidArgumentException('Cartão não encontrado.', 404);
        }

        return $this->obter($id, $userId);
    }

    public function excluir(int $id, int $userId): void
    {
        $this->obter($id, $userId);
        $this->cartoes->excluir($id, $userId);
    }

    private function validar(array $dados): array
    {
        $nome = trim((string) ($dados['nome'] ?? ''));
        $ultimosDigitos = trim((string) ($dados['ultimos_digitos'] ?? ''));

        if ($nome === '') {
            throw new InvalidArgumentException('Nome do cartão é obrigatório.');
        }

        if (!preg_match('/^\d{4}$/', $ultimosDigitos)) {
            throw new InvalidArgumentException('Informe os 4 últimos dígitos do cartão.');
        }

        $diaFechamento = (int) ($dados['dia_fechamento'] ?? 0);
        $diaVencimento = (int) ($dados['dia_vencimento'] ?? 0);

        if ($diaFechamento < 1 || $diaFechamento > 31) {
            throw new InvalidArgumentException('Dia de fechamento precisa estar entre 1 e 31.');
        }

        if ($diaVencimento < 1 || $diaVencimento > 31) {
            throw new InvalidArgumentException('Dia de vencimento precisa estar entre 1 e 31.');
        }

        $cor = (string) ($dados['cor'] ?? '#8b5cf6');
        if (!Validator::corHexValida($cor)) {
            throw new InvalidArgumentException('Cor inválida.');
        }

        $limite = Money::normalizarEntrada((string) ($dados['limite'] ?? '0'));

        return [
            'nome' => $nome,
            'banco' => !empty($dados['banco']) ? trim((string) $dados['banco']) : null,
            'ultimos_digitos' => $ultimosDigitos,
            'limite' => $limite,
            'dia_fechamento' => $diaFechamento,
            'dia_vencimento' => $diaVencimento,
            'cor' => $cor,
        ];
    }
}
