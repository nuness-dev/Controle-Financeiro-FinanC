<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Repository\AttachmentRepository;
use App\Service\TransactionService;
use App\Service\UploadService;
use InvalidArgumentException;
use RuntimeException;

final class TransactionController
{
    private TransactionService $service;
    private AttachmentRepository $anexos;

    public function __construct(?TransactionService $service = null, ?AttachmentRepository $anexos = null)
    {
        $this->service = $service ?? new TransactionService();
        $this->anexos = $anexos ?? new AttachmentRepository();
    }

    public function listar(Request $request): void
    {
        $resultado = $this->service->listar(
            Auth::id(),
            [
                'search' => $request->query('search'),
                'tipo' => $request->query('tipo'),
                'status' => $request->query('status'),
                'category_id' => $request->query('category_id'),
                'account_id' => $request->query('account_id'),
                'data_inicio' => $request->query('data_inicio'),
                'data_fim' => $request->query('data_fim'),
                'valor_min' => $request->query('valor_min'),
                'valor_max' => $request->query('valor_max'),
            ],
            (string) $request->query('sort', 'data'),
            (string) $request->query('direction', 'desc'),
            (int) $request->query('page', 1),
            (int) $request->query('per_page', 15)
        );

        Response::success($resultado);
    }

    public function obter(int $id): void
    {
        try {
            $transacao = $this->service->obter($id, Auth::id());
            $transacao['anexos'] = $this->anexos->listarPorTransacao($id, Auth::id());
            Response::success($transacao);
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    public function criar(Request $request): void
    {
        Csrf::requireValid((string) $request->input('csrf_token'));

        try {
            Response::success($this->service->criar(Auth::id(), $request->all()), 201);
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
        } catch (RuntimeException $e) {
            Response::error('Não foi possível criar a transação.', 500);
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
            Response::error('Não foi possível atualizar a transação.', 500);
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
            Response::error('Não foi possível excluir a transação.', 500);
        }
    }

    public function upload(int $id): void
    {
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            Response::error('Sessão expirada ou requisição inválida.', 403);
        }

        if (empty($_FILES['anexo'])) {
            Response::error('Nenhum arquivo enviado.', 400);
        }

        try {
            $this->service->obter($id, Auth::id());

            $uploadService = new UploadService();
            $dados = $uploadService->processarAnexoFinanceiro($_FILES['anexo']);

            $anexoId = $this->anexos->criar($id, Auth::id(), $dados);

            Response::success(['id' => $anexoId] + $dados, 201);
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
        } catch (RuntimeException $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function excluirAnexo(Request $request, int $anexoId): void
    {
        Csrf::requireValid((string) $request->input('csrf_token'));

        $anexo = $this->anexos->buscarPorIdEUsuario($anexoId, Auth::id());
        if ($anexo === null) {
            Response::error('Anexo não encontrado.', 404);
        }

        try {
            $uploadService = new UploadService();
            $uploadService->removerArquivo($anexo['nome_arquivo']);
            $this->anexos->excluir($anexoId, Auth::id());

            Response::success();
        } catch (RuntimeException $e) {
            Response::error('Não foi possível excluir o anexo.', 500);
        }
    }
}
