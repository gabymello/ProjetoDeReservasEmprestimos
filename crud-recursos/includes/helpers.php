<?php
/**
 * Funções auxiliares do sistema.
 */

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', 'uploads/');

/** Status possíveis do recurso (mesmo ENUM da tabela `recurso`) */
const STATUS_RECURSO = ['Disponível', 'Em uso', 'Manutenção', 'Inativo'];

/**
 * Trata o upload de uma imagem de forma segura.
 *
 * @param array $file       Referência a um item de $_FILES
 * @param string|null $old  Nome do arquivo antigo (para exclusão em edições)
 * @return array{ok:bool, filename:?string, error:?string}
 */
function uploadFoto(array $file, ?string $old = null): array
{
    // Nenhum arquivo enviado — mantém o que já existia (ou nulo)
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'filename' => $old, 'error' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'filename' => null, 'error' => 'Falha no upload da imagem.'];
    }

    $permitidos = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($permitidos[$mimeType])) {
        return ['ok' => false, 'filename' => null, 'error' => 'Formato de imagem não suportado. Use JPG, PNG, WEBP ou GIF.'];
    }

    if ($file['size'] > 5 * 1024 * 1024) { // 5MB
        return ['ok' => false, 'filename' => null, 'error' => 'A imagem deve ter no máximo 5MB.'];
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $extensao = $permitidos[$mimeType];
    $novoNome = uniqid('recurso_', true) . '.' . $extensao;
    $destino  = UPLOAD_DIR . $novoNome;

    if (!move_uploaded_file($file['tmp_name'], $destino)) {
        return ['ok' => false, 'filename' => null, 'error' => 'Não foi possível salvar a imagem no servidor.'];
    }

    // Remove a foto antiga, se existir
    if ($old && is_file(UPLOAD_DIR . $old)) {
        unlink(UPLOAD_DIR . $old);
    }

    return ['ok' => true, 'filename' => $novoNome, 'error' => null];
}

function removerFoto(?string $nomeArquivo): void
{
    if ($nomeArquivo && is_file(UPLOAD_DIR . $nomeArquivo)) {
        unlink(UPLOAD_DIR . $nomeArquivo);
    }
}

function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Retorna a classe CSS de badge de acordo com o status do recurso.
 */
function statusBadgeClass(string $status): string
{
    return match ($status) {
        'Disponível' => 'badge-status badge-status--disponivel',
        'Em uso'     => 'badge-status badge-status--em-uso',
        'Manutenção' => 'badge-status badge-status--manutencao',
        'Inativo'    => 'badge-status badge-status--inativo',
        default      => 'badge-status',
    };
}
