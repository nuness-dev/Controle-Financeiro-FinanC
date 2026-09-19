<?php

declare(strict_types=1);

namespace App\Service;

use InvalidArgumentException;
use RuntimeException;

/**
 * Anexo financeiro e foto de perfil ficam fora de public/ e são servidos por
 * scripts que conferem o login antes de entregar o arquivo.
 */
final class UploadService
{
    private const MIME_PERMITIDOS_ANEXO = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    private const MIME_PERMITIDOS_FOTO = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private const TAMANHO_MAXIMO_BYTES = 5 * 1024 * 1024;

    public function processarAnexoFinanceiro(array $arquivo): array
    {
        $extensao = $this->validarArquivo($arquivo, self::MIME_PERMITIDOS_ANEXO);
        $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $extensao;
        $destino = $this->diretorioUploads() . '/' . $nomeArquivo;

        if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
            throw new RuntimeException('Não foi possível salvar o arquivo.');
        }

        return [
            'nome_original' => basename($arquivo['name']),
            'nome_arquivo' => $nomeArquivo,
            'mime' => mime_content_type($destino),
            'tamanho' => filesize($destino),
        ];
    }

    public function removerArquivo(string $nomeArquivo): void
    {
        $caminho = $this->diretorioUploads() . '/' . basename($nomeArquivo);

        if (is_file($caminho)) {
            unlink($caminho);
        }
    }

    public function caminhoAnexo(string $nomeArquivo): string
    {
        return $this->diretorioUploads() . '/' . basename($nomeArquivo);
    }

    public function processarFotoPerfil(array $arquivo): string
    {
        $extensao = $this->validarArquivo($arquivo, self::MIME_PERMITIDOS_FOTO);
        $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $extensao;
        $destino = $this->diretorioFotosPerfil() . '/' . $nomeArquivo;

        if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
            throw new RuntimeException('Não foi possível salvar a foto.');
        }

        return $nomeArquivo;
    }

    public function removerFotoPerfil(?string $nomeArquivo): void
    {
        if (empty($nomeArquivo)) {
            return;
        }

        $caminho = $this->diretorioFotosPerfil() . '/' . basename($nomeArquivo);

        if (is_file($caminho)) {
            unlink($caminho);
        }
    }

    public function caminhoFotoPerfil(string $nomeArquivo): string
    {
        return $this->diretorioFotosPerfil() . '/' . basename($nomeArquivo);
    }

    private function validarArquivo(array $arquivo, array $mimesPermitidos): string
    {
        if (empty($arquivo['tmp_name']) || !is_uploaded_file($arquivo['tmp_name'])) {
            throw new InvalidArgumentException('Arquivo inválido.');
        }

        if (($arquivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Falha no upload do arquivo.');
        }

        if ((int) $arquivo['size'] > self::TAMANHO_MAXIMO_BYTES) {
            throw new InvalidArgumentException('Arquivo maior que o limite permitido (5MB).');
        }

        // Nunca confia na extensão nem no Content-Type enviado pelo navegador —
        // o MIME real é lido do conteúdo do arquivo via fileinfo.
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeReal = finfo_file($finfo, $arquivo['tmp_name']);
        finfo_close($finfo);

        if (!isset($mimesPermitidos[$mimeReal])) {
            throw new InvalidArgumentException('Tipo de arquivo não permitido.');
        }

        return $mimesPermitidos[$mimeReal];
    }

    private function diretorioUploads(): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/uploads';
        $this->garantirDiretorio($dir);

        return $dir;
    }

    private function diretorioFotosPerfil(): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/avatars';
        $this->garantirDiretorio($dir);

        return $dir;
    }

    private function garantirDiretorio(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
