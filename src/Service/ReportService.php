<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use DateTimeImmutable;
use InvalidArgumentException;

final class ReportService
{
    private TransactionRepository $transacoes;
    private AccountRepository $contas;

    public function __construct(?TransactionRepository $transacoes = null, ?AccountRepository $contas = null)
    {
        $this->transacoes = $transacoes ?? new TransactionRepository();
        $this->contas = $contas ?? new AccountRepository();
    }

    /**
     * Resumo usado tanto pelo dashboard quanto pela página de relatórios —
     * mesma fonte de dados, evita divergência entre as duas telas.
     */
    public function resumo(int $userId, string $periodo, ?string $dataInicioCustom, ?string $dataFimCustom): array
    {
        [$dataInicio, $dataFim] = $this->resolverPeriodo($periodo, $dataInicioCustom, $dataFimCustom);

        $saldo = $this->contas->calcularSaldoTotal($userId);
        $resumoPeriodo = $this->transacoes->resumoPeriodo($userId, $dataInicio, $dataFim);

        return [
            'periodo' => ['inicio' => $dataInicio, 'fim' => $dataFim],
            'saldo_atual' => $saldo['atual'],
            'saldo_previsto' => $saldo['previsto'],
            'receitas' => $resumoPeriodo['receita'],
            'despesas' => $resumoPeriodo['despesa'],
            'gastos_por_categoria' => $this->transacoes->gastosPorCategoria($userId, $dataInicio, $dataFim),
            'evolucao' => $this->transacoes->evolucaoDiaria($userId, $dataInicio, $dataFim),
            'ultimas_transacoes' => $this->transacoes->ultimas($userId, 6),
            'proximos_vencimentos' => $this->transacoes->proximosVencimentos($userId, 5),
            'contas_pendentes' => $this->contarPendentes($userId),
        ];
    }

    public function exportarCsv(int $userId, string $periodo, ?string $dataInicioCustom, ?string $dataFimCustom): string
    {
        [$dataInicio, $dataFim] = $this->resolverPeriodo($periodo, $dataInicioCustom, $dataFimCustom);

        $resultado = $this->transacoes->listarPaginado(
            $userId,
            ['data_inicio' => $dataInicio, 'data_fim' => $dataFim],
            ['coluna' => 'data', 'direcao' => 'ASC'],
            1,
            10000
        );

        $linhas = "Data;Descrição;Tipo;Categoria;Conta;Valor;Status\n";
        foreach ($resultado['dados'] as $t) {
            $linhas .= sprintf(
                "%s;%s;%s;%s;%s;%s;%s\n",
                $this->paraDataBr($t['data']),
                str_replace(';', ',', $t['descricao']),
                $t['tipo'],
                $t['categoria_nome'] ?? 'Sem categoria',
                $t['conta_nome'] ?? '',
                str_replace('.', ',', $t['valor']),
                $t['status']
            );
        }

        return $linhas;
    }

    private function contarPendentes(int $userId): int
    {
        $resultado = $this->transacoes->listarPaginado(
            $userId,
            ['status' => 'pendente'],
            ['coluna' => 'criado_em', 'direcao' => 'DESC'],
            1,
            1
        );

        return $resultado['total'];
    }

    /**
     * @return array{0: string, 1: string} [data_inicio, data_fim] em ISO
     */
    private function resolverPeriodo(string $periodo, ?string $dataInicioCustom, ?string $dataFimCustom): array
    {
        $hoje = new DateTimeImmutable('today');

        switch ($periodo) {
            case 'mes_anterior':
                $inicio = $hoje->modify('first day of last month');
                $fim = $hoje->modify('last day of last month');
                break;
            case 'ultimos_30':
                $inicio = $hoje->modify('-29 days');
                $fim = $hoje;
                break;
            case 'ultimos_90':
                $inicio = $hoje->modify('-89 days');
                $fim = $hoje;
                break;
            case 'personalizado':
                if (empty($dataInicioCustom) || empty($dataFimCustom)) {
                    throw new InvalidArgumentException('Informe o período personalizado (início e fim).');
                }
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataInicioCustom) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFimCustom)) {
                    throw new InvalidArgumentException('Período personalizado inválido.');
                }
                if ($dataInicioCustom > $dataFimCustom) {
                    throw new InvalidArgumentException('A data inicial não pode ser depois da data final.');
                }

                return [$dataInicioCustom, $dataFimCustom];
            case 'este_mes':
            default:
                $inicio = $hoje->modify('first day of this month');
                $fim = $hoje->modify('last day of this month');
                break;
        }

        return [$inicio->format('Y-m-d'), $fim->format('Y-m-d')];
    }

    private function paraDataBr(string $dataIso): string
    {
        $partes = explode('-', substr($dataIso, 0, 10));

        return count($partes) === 3 ? "{$partes[2]}/{$partes[1]}/{$partes[0]}" : $dataIso;
    }
}
