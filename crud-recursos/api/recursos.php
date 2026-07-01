<?php
/**
 * API JSON de Recursos
 * ---------------------------------------------------------
 * Endpoints:
 *
 *   GET    /api/recursos.php            → lista todos os recursos
 *   GET    /api/recursos.php?id=1       → busca um recurso específico
 *   POST   /api/recursos.php            → cria um recurso
 *                                          (multipart/form-data se enviar foto,
 *                                           ou application/json se for só texto)
 *   POST   /api/recursos.php?id=1       → atualiza um recurso
 *          + campo _method=PUT           (necessário para permitir upload de
 *                                          foto, já que PHP não processa
 *                                          multipart em requisições PUT)
 *   PUT    /api/recursos.php?id=1       → atualiza um recurso via JSON
 *                                          (sem upload de foto)
 *   DELETE /api/recursos.php?id=1       → exclui um recurso
 *
 * Todas as respostas são em JSON.
 * ---------------------------------------------------------
 */

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

// -----------------------------------------------------------------
// CORS: libera o consumo da API por apps externos (Android, iOS,
// front-end separado, etc.). Ajuste Access-Control-Allow-Origin
// para um domínio específico em produção, se quiser restringir.
// -----------------------------------------------------------------
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Requisições OPTIONS (preflight do navegador) não precisam de lógica
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/**
 * Encerra a execução devolvendo um JSON padronizado.
 */
function jsonResponse(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Monta a URL pública da foto (ou null).
 */
function fotoUrl(?string $arquivo): ?string
{
    if (!$arquivo) {
        return null;
    }
    $base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://')
        . $_SERVER['HTTP_HOST']
        . rtrim(dirname($_SERVER['SCRIPT_NAME'], 1), '/');
    return $base . '/../' . UPLOAD_URL . $arquivo;
}

function recursoParaArray(array $r): array
{
    return [
        'id'            => (int)$r['id'],
        'nome'          => $r['nome'],
        'descricao'     => $r['descricao'],
        'categoria'     => [
            'id'   => $r['categoria_id'] !== null ? (int)$r['categoria_id'] : null,
            'nome' => $r['categoria_nome'] ?? null,
        ],
        'setor'         => [
            'id'   => $r['setor_id'] !== null ? (int)$r['setor_id'] : null,
            'nome' => $r['setor_nome'] ?? null,
        ],
        'foto'          => $r['foto'],
        'foto_url'      => fotoUrl($r['foto']),
        'criado_em'     => $r['criado_em'] ?? null,
        'atualizado_em' => $r['atualizado_em'] ?? null,
    ];
}

const SELECT_BASE = "SELECT r.*, c.nome AS categoria_nome, s.nome AS setor_nome
                      FROM recursos r
                      LEFT JOIN categorias c ON c.id = r.categoria_id
                      LEFT JOIN setores s ON s.id = r.setor_id";

// -----------------------------------------------------------------
// Descobre o método efetivo (permite override via campo _method,
// necessário para update com upload de foto usando POST).
// -----------------------------------------------------------------
$metodo = $_SERVER['REQUEST_METHOD'];
if ($metodo === 'POST' && isset($_POST['_method'])) {
    $metodo = strtoupper($_POST['_method']);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    switch ($metodo) {

        // ---------------------------------------------------------
        // GET: lista ou detalhe
        // ---------------------------------------------------------
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare(SELECT_BASE . " WHERE r.id = :id");
                $stmt->execute([':id' => $id]);
                $recurso = $stmt->fetch();

                if (!$recurso) {
                    jsonResponse(404, ['erro' => 'Recurso não encontrado.']);
                }

                jsonResponse(200, ['dado' => recursoParaArray($recurso)]);
            }

            $stmt = $pdo->query(SELECT_BASE . " ORDER BY r.id DESC");
            $recursos = array_map('recursoParaArray', $stmt->fetchAll());

            jsonResponse(200, ['total' => count($recursos), 'dados' => $recursos]);
            break;

        // ---------------------------------------------------------
        // POST: cria (ou atualiza, se vier com _method=PUT + id)
        // ---------------------------------------------------------
        case 'POST':
            $entrada = $_POST;

            // Suporte a JSON puro quando não há upload de arquivo
            if (empty($entrada) && empty($_FILES)) {
                $json = json_decode(file_get_contents('php://input'), true) ?: [];
                $entrada = $json;
            }

            $nome        = trim($entrada['nome'] ?? '');
            $descricao   = trim($entrada['descricao'] ?? '');
            $categoriaId = isset($entrada['categoria_id']) && $entrada['categoria_id'] !== ''
                ? (int)$entrada['categoria_id'] : null;
            $setorId     = isset($entrada['setor_id']) && $entrada['setor_id'] !== ''
                ? (int)$entrada['setor_id'] : null;

            if ($nome === '') {
                jsonResponse(422, ['erro' => 'O campo "nome" é obrigatório.']);
            }

            // --- Atualização (id presente na querystring) ---
            if ($id) {
                $stmt = $pdo->prepare("SELECT * FROM recursos WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $existente = $stmt->fetch();

                if (!$existente) {
                    jsonResponse(404, ['erro' => 'Recurso não encontrado.']);
                }

                $nomeFoto = $existente['foto'];

                if (!empty($_FILES['foto']['name'])) {
                    $upload = uploadFoto($_FILES['foto'], $nomeFoto);
                    if (!$upload['ok']) {
                        jsonResponse(422, ['erro' => $upload['error']]);
                    }
                    $nomeFoto = $upload['filename'];
                }

                $update = $pdo->prepare(
                    "UPDATE recursos
                     SET nome = :nome, descricao = :descricao, categoria_id = :categoria_id,
                         setor_id = :setor_id, foto = :foto
                     WHERE id = :id"
                );
                $update->execute([
                    ':nome'         => $nome,
                    ':descricao'    => $descricao !== '' ? $descricao : null,
                    ':categoria_id' => $categoriaId,
                    ':setor_id'     => $setorId,
                    ':foto'         => $nomeFoto,
                    ':id'           => $id,
                ]);

                $stmt = $pdo->prepare(SELECT_BASE . " WHERE r.id = :id");
                $stmt->execute([':id' => $id]);

                jsonResponse(200, ['mensagem' => 'Recurso atualizado.', 'dado' => recursoParaArray($stmt->fetch())]);
            }

            // --- Criação ---
            $nomeFoto = null;
            if (!empty($_FILES['foto']['name'])) {
                $upload = uploadFoto($_FILES['foto']);
                if (!$upload['ok']) {
                    jsonResponse(422, ['erro' => $upload['error']]);
                }
                $nomeFoto = $upload['filename'];
            }

            $insert = $pdo->prepare(
                "INSERT INTO recursos (nome, descricao, categoria_id, setor_id, foto)
                 VALUES (:nome, :descricao, :categoria_id, :setor_id, :foto)"
            );
            $insert->execute([
                ':nome'         => $nome,
                ':descricao'    => $descricao !== '' ? $descricao : null,
                ':categoria_id' => $categoriaId,
                ':setor_id'     => $setorId,
                ':foto'         => $nomeFoto,
            ]);

            $novoId = (int)$pdo->lastInsertId();
            $stmt = $pdo->prepare(SELECT_BASE . " WHERE r.id = :id");
            $stmt->execute([':id' => $novoId]);

            jsonResponse(201, ['mensagem' => 'Recurso criado.', 'dado' => recursoParaArray($stmt->fetch())]);
            break;

        // ---------------------------------------------------------
        // PUT: atualização apenas de texto (sem upload de foto)
        // ---------------------------------------------------------
        case 'PUT':
            if (!$id) {
                jsonResponse(422, ['erro' => 'Informe o parâmetro "id" na URL.']);
            }

            $stmt = $pdo->prepare("SELECT * FROM recursos WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $existente = $stmt->fetch();

            if (!$existente) {
                jsonResponse(404, ['erro' => 'Recurso não encontrado.']);
            }

            $entrada = json_decode(file_get_contents('php://input'), true) ?: [];

            $nome        = trim($entrada['nome'] ?? $existente['nome']);
            $descricao   = array_key_exists('descricao', $entrada) ? trim($entrada['descricao']) : $existente['descricao'];
            $categoriaId = array_key_exists('categoria_id', $entrada)
                ? ($entrada['categoria_id'] !== '' && $entrada['categoria_id'] !== null ? (int)$entrada['categoria_id'] : null)
                : $existente['categoria_id'];
            $setorId     = array_key_exists('setor_id', $entrada)
                ? ($entrada['setor_id'] !== '' && $entrada['setor_id'] !== null ? (int)$entrada['setor_id'] : null)
                : $existente['setor_id'];

            if ($nome === '') {
                jsonResponse(422, ['erro' => 'O campo "nome" é obrigatório.']);
            }

            $update = $pdo->prepare(
                "UPDATE recursos
                 SET nome = :nome, descricao = :descricao, categoria_id = :categoria_id, setor_id = :setor_id
                 WHERE id = :id"
            );
            $update->execute([
                ':nome'         => $nome,
                ':descricao'    => $descricao !== '' ? $descricao : null,
                ':categoria_id' => $categoriaId,
                ':setor_id'     => $setorId,
                ':id'           => $id,
            ]);

            $stmt = $pdo->prepare(SELECT_BASE . " WHERE r.id = :id");
            $stmt->execute([':id' => $id]);

            jsonResponse(200, ['mensagem' => 'Recurso atualizado.', 'dado' => recursoParaArray($stmt->fetch())]);
            break;

        // ---------------------------------------------------------
        // DELETE: exclui recurso e sua foto física
        // ---------------------------------------------------------
        case 'DELETE':
            if (!$id) {
                jsonResponse(422, ['erro' => 'Informe o parâmetro "id" na URL.']);
            }

            $stmt = $pdo->prepare("SELECT foto FROM recursos WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $recurso = $stmt->fetch();

            if (!$recurso) {
                jsonResponse(404, ['erro' => 'Recurso não encontrado.']);
            }

            $pdo->prepare("DELETE FROM recursos WHERE id = :id")->execute([':id' => $id]);
            removerFoto($recurso['foto']);

            jsonResponse(200, ['mensagem' => 'Recurso excluído com sucesso.']);
            break;

        // ---------------------------------------------------------
        default:
            jsonResponse(405, ['erro' => 'Método não permitido.']);
    }
} catch (Throwable $e) {
    jsonResponse(500, ['erro' => 'Erro interno no servidor.', 'detalhe' => $e->getMessage()]);
}
