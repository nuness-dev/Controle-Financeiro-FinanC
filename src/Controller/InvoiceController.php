<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Service\InvoiceService;
use InvalidArgumentException;
use RuntimeException;

final class InvoiceController
{
    private InvoiceService $service;

    public function __construct(?InvoiceService $service = null)
    {
        $this->service = $service ?? new InvoiceService();
    }

    public function listarPorCartao(Request $request): void
    {
        $cardId = (int) $request->query('card_id', 0);

        try {
            Response::success(['faturas' => $this->service->listarPorCartao($cardId, Auth::id())]);
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    public function obter(int $id): void
    {
        try {
            $fatura = $this->service->obter($id, Auth::id());
            $fatura['compras'] = $this->service->listarComprasPorCartao((int) $fatura['card_id'], Auth::id());
            Response::success($fatura);
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    public function registrarCompra(Request $request): void
    {
        Csrf::requireValid((string) $request->input('csrf_token'));

        $cardId = (int) $request->input('card_id', 0);

        try {
            Response::success($this->service->registrarCompra(Auth::id(), $cardId, $request->all()), 201);
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 422);
        } catch (RuntimeException $e) {
            Response::error('Não foi possível registrar a compra.', 500);
        }
    }

    public function pagar(Request $request, int $id): void
    {
        Csrf::requireValid((string) $request->input('csrf_token'));

        $contaId = (int) $request->input('account_id', 0);

        try {
            Response::success($this->service->pagarFatura($id, Auth::id(), $contaId));
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 422);
        } catch (RuntimeException $e) {
            Response::error('Não foi possível registrar o pagamento.', 500);
        }
    }
}
