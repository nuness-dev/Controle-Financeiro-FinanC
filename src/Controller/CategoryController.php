<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Repository\CategoryRepository;
use InvalidArgumentException;
use RuntimeException;

final class CategoryController
{
    private CategoryRepository $repository;

    public function __construct(?CategoryRepository $repository = null)
    {
        $this->repository = $repository ?? new CategoryRepository();
    }

    public function listar(Request $request): void
    {
        $tipo = $request->query('tipo');
        Response::success(['categorias' => $this->repository->listarPorUsuario(Auth::id(), $tipo ?: null)]);
    }

    public function criar(Request $request): void
    {
        Csrf::requireValid((string) $request->input('csrf_token'));

        try {
            $dados = $this->validar($request);
            $id = $this->repository->criar(Auth::id(), $dados);

            Response::success(array_merge(['id' => $id], $dados), 201);
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
        } catch (RuntimeException $e) {
            Response::error('Não foi possível criar a categoria.', 500);
        }
    }

    public function atualizar(Request $request, int $id): void
    {
        Csrf::requireValid((string) $request->input('csrf_token'));

        try {
            $dados = $this->validar($request);
            $dados['ativa'] = $request->input('ativa', true) ? 1 : 0;

            if (!$this->repository->atualizar($id, Auth::id(), $dados)) {
                Response::error('Categoria não encontrada.', 404);
            }

            Response::success(array_merge(['id' => $id], $dados));
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
        } catch (RuntimeException $e) {
            Response::error('Não foi possível atualizar a categoria.', 500);
        }
    }

    public function excluir(Request $request, int $id): void
    {
        Csrf::requireValid((string) $request->input('csrf_token'));

        try {
            if (!$this->repository->excluir($id, Auth::id())) {
                Response::error('Categoria não encontrada.', 404);
            }

            Response::success();
        } catch (RuntimeException $e) {
            Response::error('Não foi possível excluir a categoria.', 500);
        }
    }

    private function validar(Request $request): array
    {
        $nome = trim((string) $request->input('nome', ''));
        $tipo = (string) $request->input('tipo', '');
        $cor = (string) $request->input('cor', '#6366f1');
        $icone = (string) $request->input('icone', '');

        if ($nome === '') {
            throw new InvalidArgumentException('Nome da categoria é obrigatório.');
        }

        if (!in_array($tipo, ['receita', 'despesa'], true)) {
            throw new InvalidArgumentException('Tipo de categoria inválido.');
        }

        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $cor)) {
            throw new InvalidArgumentException('Cor inválida.');
        }

        return ['nome' => $nome, 'tipo' => $tipo, 'cor' => $cor, 'icone' => $icone ?: null];
    }
}
