<?php

declare(strict_types=1);

namespace App\Service;

use App\Helpers\Money;
use App\Helpers\Validator;
use App\Repository\GoalRepository;
use InvalidArgumentException;

final class GoalService
{
    private GoalRepository $metas;

    public function __construct(?GoalRepository $metas = null)
    {
        $this->metas = $metas ?? new GoalRepository();
    }

    public function listar(int $userId): array
    {
        $metas = $this->metas->listarPorUsuario($userId);

        foreach ($metas as &$meta) {
            $meta['concluida'] = (float) $meta['valor_atual'] >= (float) $meta['valor_objetivo'];
            $meta['percentual'] = (float) $meta['valor_objetivo'] > 0
                ? round(min(100, ((float) $meta['valor_atual'] / (float) $meta['valor_objetivo']) * 100), 1)
                : 0.0;
        }

        return $metas;
    }

    public function obter(int $id, int $userId): array
    {
        $meta = $this->metas->buscarPorIdEUsuario($id, $userId);

        if ($meta === null) {
            throw new InvalidArgumentException('Meta não encontrada.', 404);
        }

        $meta['contribuicoes'] = $this->metas->listarContribuicoes($id, $userId);
        $meta['concluida'] = (float) $meta['valor_atual'] >= (float) $meta['valor_objetivo'];

        return $meta;
    }

    public function criar(int $userId, array $dados): int
    {
        return $this->metas->criar($userId, $this->validar($dados));
    }

    public function atualizar(int $id, int $userId, array $dados): array
    {
        if (!$this->metas->atualizar($id, $userId, $this->validar($dados))) {
            throw new InvalidArgumentException('Meta não encontrada.', 404);
        }

        return $this->obter($id, $userId);
    }

    public function excluir(int $id, int $userId): void
    {
        $this->obter($id, $userId);
        $this->metas->excluir($id, $userId);
    }

    public function contribuir(int $id, int $userId, array $dados): array
    {
        $this->obter($id, $userId);

        $valor = Money::normalizarEntrada((string) ($dados['valor'] ?? ''));
        if (!Money::ehPositivo($valor)) {
            throw new InvalidArgumentException('O valor da contribuição precisa ser positivo.');
        }

        $observacao = !empty($dados['observacao']) ? trim((string) $dados['observacao']) : null;

        $this->metas->adicionarContribuicao($id, $userId, $valor, $observacao);

        return $this->obter($id, $userId);
    }

    private function validar(array $dados): array
    {
        $titulo = trim((string) ($dados['titulo'] ?? ''));
        if ($titulo === '') {
            throw new InvalidArgumentException('Título da meta é obrigatório.');
        }

        $valorObjetivo = Money::normalizarEntrada((string) ($dados['valor_objetivo'] ?? ''));
        if (!Money::ehPositivo($valorObjetivo)) {
            throw new InvalidArgumentException('O valor objetivo precisa ser positivo.');
        }

        $cor = (string) ($dados['cor'] ?? '#22c55e');
        if (!Validator::corHexValida($cor)) {
            throw new InvalidArgumentException('Cor inválida.');
        }

        return [
            'titulo' => $titulo,
            'valor_objetivo' => $valorObjetivo,
            'prazo' => !empty($dados['prazo']) ? $dados['prazo'] : null,
            'cor' => $cor,
        ];
    }
}
