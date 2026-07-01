<?php
/**
 * API JSON de Recursos — banco `sistema_reservas`
 * ---------------------------------------------------------
 * Endpoints:
 *
 *   GET    /api/recursos.php                 → lista recursos
 *          Filtros opcionais: ?categoria=1  ?setor=2  ?status=Disponível
 *   GET    /api/recursos.php?id=1            → busca um recurso específico
 *   POST   /api/recursos.php                 → cria um recurso
 *                                               (multipart/form-data se enviar foto,
 *                                                ou application/json se for só texto)
 *   POST   /api/recursos.php?id=1            → atualiza um recurso
 *          + campo _method=PUT                 (necessário para permitir upload de
 *                                                foto, já que PHP não processa
 *                                                multipart em requisições PUT)
 *   PUT    /api/recursos.php?id=1            → atualiza um recurso via JSON
 *                                               (sem upload de foto)
 *   DELETE /api/recursos.php?id=1            → exclui um recurso
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

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function jsonResponse(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

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
        'id'          => (int)$r['id_recurso'],
        'nome'        => $r['nome'],
        'descricao'   => $r['descricao'],
        'categoria'   => [
            'id'   => (int)$r['id_categoria'],
            'nome' => $r['categoria_nome'] ?? null,
        ],
        'setor'       => [
            'id'   => (int)$r['id_setor'],
            'nome' => $r['setor_nome'] ?? null,
        ],
        'status'         => $r['status'],
        'localizacao'    => $r['localizacao'],
        'foto'           => $r['foto'],
        'foto_url'       => fotoUrl($r['foto']),
        'data_cadastro'  => $r['data_cadastro'] ?? null,
    ];
}

const SELECT_BASE = "SELECT r.*, c.nome AS categoria_nome, s.nome AS setor_nome
                      FROM recurso r
                      INNER JOIN categoria c ON c.id_categoria = r.id_categoria
                      INNER JOIN setor s ON s.id_setor = r.id_setor";

$metodo = $_SERVER['REQUEST_METHOD'];
if ($metodo === 'POST' && isset($_POST['_method'])) {
    $metodo = strtoupper($_POST['_method']);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    switch ($metodo) {

        // ---------------------------------------------------------
        // GET: lista (com filtros opcionais) ou detalhe
        // ---------------------------------------------------------
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare(SELECT_BASE . " WHERE r.id_recurso = :id");
                $stmt->execute([':id' => $id]);
                $recurso = $stmt->fetch();

                if (!$recurso) {
                    jsonResponse(404, ['erro' => 'Recurso não encontrado.']);
                }

                jsonResponse(200, ['dado' => recursoParaArray($recurso)]);
            }

            $condicoes = [];
            $parametros = [];

            if (isset($_GET['categoria']) && $_GET['categoria'] !== '') {
                $condicoes[] = 'r.id_categoria = :categoria';
                $parametros[':categoria'] = (int)$_GET['categoria'];
            }
            if (isset($_GET['setor']) && $_GET['setor'] !== '') {
                $condicoes[] = 'r.id_setor = :setor';
                $parametros[':setor'] = (int)$_GET['setor'];
            }
            if (isset($_GET['status']) && in_array($_GET['status'], STATUS_RECURSO, true)) {
                $condicoes[] = 'r.status = :status';
                $parametros[':status'] = $_GET['status'];
            }

            $where = $condicoes ? 'WHERE ' . implode(' AND ', $condicoes) : '';

            $stmt = $pdo->prepare(SELECT_BASE . " $where ORDER BY r.id_recurso DESC");
            $stmt->execute($parametros);
            $recursos = array_map('recursoParaArray', $stmt->fetchAll());

            jsonResponse(200, ['total' => count($recursos), 'dados' => $recursos]);
            break;

        // ---------------------------------------------------------
        // POST: cria (ou atualiza, se vier com _method=PUT + id)
        // ---------------------------------------------------------
        case 'POST':
            $entrada = $_POST;

            if (empty($entrada) && empty($_FILES)) {
                $entrada = json_decode(file_get_contents('php://input'), true) ?: [];
            }

            $nome        = trim($entrada['nome'] ?? '');
            $descricao   = trim($entrada['descricao'] ?? '');
            $idCategoria = isset($entrada['id_categoria']) && $entrada['id_categoria'] !== '' ? (int)$entrada['id_categoria'] : null;
            $idSetor     = isset($entrada['id_setor']) && $entrada['id_setor'] !== '' ? (int)$entrada['id_setor'] : null;
            $status      = $entrada['status'] ?? 'Disponível';
            $localizacao = trim($entrada['localizacao'] ?? '');

            if ($nome === '') {
                jsonResponse(422, ['erro' => 'O campo "nome" é obrigatório.']);
            }
            if (!$idCategoria) {
                jsonResponse(422, ['erro' => 'O campo "id_categoria" é obrigatório.']);
            }
            if (!$idSetor) {
                jsonResponse(422, ['erro' => 'O campo "id_setor" é obrigatório.']);
            }
            if (!in_array($status, STATUS_RECURSO, true)) {
                jsonResponse(422, ['erro' => 'Status inválido. Use um de: ' . implode(', ', STATUS_RECURSO)]);
            }

            // --- Atualização (id presente na querystring) ---
            if ($id) {
                $stmt = $pdo->prepare("SELECT * FROM recurso WHERE id_recurso = :id");
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
                    "UPDATE recurso
                     SET nome = :nome, descricao = :descricao, id_categoria = :id_categoria,
                         id_setor = :id_setor, status = :status, localizacao = :localizacao, foto = :foto
                     WHERE id_recurso = :id"
                );
                $update->execute([
                    ':nome'         => $nome,
                    ':descricao'    => $descricao !== '' ? $descricao : null,
                    ':id_categoria' => $idCategoria,
                    ':id_setor'     => $idSetor,
                    ':status'       => $status,
                    ':localizacao'  => $localizacao !== '' ? $localizacao : null,
                    ':foto'         => $nomeFoto,
                    ':id'           => $id,
                ]);

                $stmt = $pdo->prepare(SELECT_BASE . " WHERE r.id_recurso = :id");
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
                "INSERT INTO recurso (nome, descricao, id_categoria, id_setor, status, localizacao, foto)
                 VALUES (:nome, :descricao, :id_categoria, :id_setor, :status, :localizacao, :foto)"
            );
            $insert->execute([
                ':nome'         => $nome,
                ':descricao'    => $descricao !== '' ? $descricao : null,
                ':id_categoria' => $idCategoria,
                ':id_setor'     => $idSetor,
                ':status'       => $status,
                ':localizacao'  => $localizacao !== '' ? $localizacao : null,
                ':foto'         => $nomeFoto,
            ]);

            $novoId = (int)$pdo->lastInsertId();
            $stmt = $pdo->prepare(SELECT_BASE . " WHERE r.id_recurso = :id");
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

            $stmt = $pdo->prepare("SELECT * FROM recurso WHERE id_recurso = :id");
            $stmt->execute([':id' => $id]);
            $existente = $stmt->fetch();

            if (!$existente) {
                jsonResponse(404, ['erro' => 'Recurso não encontrado.']);
            }

            $entrada = json_decode(file_get_contents('php://input'), true) ?: [];

            $nome        = trim($entrada['nome'] ?? $existente['nome']);
            $descricao   = array_key_exists('descricao', $entrada) ? trim($entrada['descricao']) : $existente['descricao'];
            $idCategoria = array_key_exists('id_categoria', $entrada) && $entrada['id_categoria'] !== ''
                ? (int)$entrada['id_categoria'] : $existente['id_categoria'];
            $idSetor     = array_key_exists('id_setor', $entrada) && $entrada['id_setor'] !== ''
                ? (int)$entrada['id_setor'] : $existente['id_setor'];
            $status      = $entrada['status'] ?? $existente['status'];
            $localizacao = array_key_exists('localizacao', $entrada) ? trim($entrada['localizacao']) : $existente['localizacao'];

            if ($nome === '') {
                jsonResponse(422, ['erro' => 'O campo "nome" é obrigatório.']);
            }
            if (!in_array($status, STATUS_RECURSO, true)) {
                jsonResponse(422, ['erro' => 'Status inválido. Use um de: ' . implode(', ', STATUS_RECURSO)]);
            }

            $update = $pdo->prepare(
                "UPDATE recurso
                 SET nome = :nome, descricao = :descricao, id_categoria = :id_categoria,
                     id_setor = :id_setor, status = :status, localizacao = :localizacao
                 WHERE id_recurso = :id"
            );
            $update->execute([
                ':nome'         => $nome,
                ':descricao'    => $descricao !== '' ? $descricao : null,
                ':id_categoria' => $idCategoria,
                ':id_setor'     => $idSetor,
                ':status'       => $status,
                ':localizacao'  => $localizacao !== '' ? $localizacao : null,
                ':id'           => $id,
            ]);

            $stmt = $pdo->prepare(SELECT_BASE . " WHERE r.id_recurso = :id");
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

            $stmt = $pdo->prepare("SELECT foto FROM recurso WHERE id_recurso = :id");
            $stmt->execute([':id' => $id]);
            $recurso = $stmt->fetch();

            if (!$recurso) {
                jsonResponse(404, ['erro' => 'Recurso não encontrado.']);
            }

            try {
                $pdo->prepare("DELETE FROM recurso WHERE id_recurso = :id")->execute([':id' => $id]);
            } catch (PDOException $e) {
                // FK de historico_uso sem ON DELETE CASCADE
                jsonResponse(409, ['erro' => 'Não é possível excluir: existem registros de histórico de uso vinculados a este recurso.']);
            }

            removerFoto($recurso['foto']);

            jsonResponse(200, ['mensagem' => 'Recurso excluído com sucesso.']);
            break;

        default:
            jsonResponse(405, ['erro' => 'Método não permitido.']);
    }
} catch (Throwable $e) {
    jsonResponse(500, ['erro' => 'Erro interno no servidor.', 'detalhe' => $e->getMessage()]);
}
