<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Response;
use App\Service\ReportService;
use InvalidArgumentException;
use RuntimeException;

final class ReportController
{
    private ReportService $service;

    public function __construct(?ReportService $service = null)
    {
        $this->service = $service ?? new ReportService();
    }

    public function resumo(): void
    {
        try {
            Response::success($this->service->resumo(
                Auth::id(),
                (string) ($_GET['periodo'] ?? 'este_mes'),
                $_GET['data_inicio'] ?? null,
                $_GET['data_fim'] ?? null
            ));
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
        } catch (RuntimeException $e) {
            Response::error('Não foi possível carregar o relatório.', 500);
        }
    }

    public function exportarCsv(): void
    {
        try {
            $csv = $this->service->exportarCsv(
                Auth::id(),
                (string) ($_GET['periodo'] ?? 'este_mes'),
                $_GET['data_inicio'] ?? null,
                $_GET['data_fim'] ?? null
            );
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
        } catch (RuntimeException $e) {
            Response::error('Não foi possível gerar o CSV.', 500);
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="financ-transacoes.csv"');
        echo "\xEF\xBB\xBF" . $csv;
        exit;
    }
}
