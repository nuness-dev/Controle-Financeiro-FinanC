<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Service\AccountService;
use InvalidArgumentException;
use RuntimeException;

final class AccountController
{
    private AccountService $service;

    public function __construct(?AccountService $service = null)
    {
        $this->service = $service ?? new AccountService();
    }

    public function listar(): void
    {
        Response::success(['contas' => $this->service->listar(Auth::id())]);
    }

    public function obter(int $id): void
    {
        try {
            Response::success($this->service->obter($id, Auth::id()));
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    public function criar(Request $request): void
    {
        Csrf::requireValid((string) $request->input('csrf_token'));

        try {
            $id = $this->service->criar(Auth::id(), $request->all());
            Response::success($this->service->obter($id, Auth::id()), 201);
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
        } catch (RuntimeException $e) {
            Response::error('Não foi possível criar a conta.', 500);
        }
    }

    public function atualizar(Request $request, int $id): void
    {
        Csrf::requireValid((string) $request->input('csrf_token'));

        try {
            Response::success($this->service->atualizar($id, Auth::id(), $request->all()));
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 422);
        } catch (RuntimeException $e) {
            Response::error('Não foi possível atualizar a conta.', 500);
        }
    }

    public function excluir(Request $request, int $id): void
    {
        Csrf::requireValid((string) $request->input('csrf_token'));

        try {
            $this->service->excluir($id, Auth::id());
            Response::success();
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 422);
        } catch (RuntimeException $e) {
            Response::error('Não foi possível excluir a conta.', 500);
        }
    }
}
