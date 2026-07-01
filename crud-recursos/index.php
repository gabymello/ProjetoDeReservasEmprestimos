<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';

$pageTitle = 'Recursos';

// -----------------------------------------------------------------
// Filtros opcionais via querystring
// -----------------------------------------------------------------
$filtroCategoria = isset($_GET['categoria']) && $_GET['categoria'] !== '' ? (int)$_GET['categoria'] : null;
$filtroSetor     = isset($_GET['setor']) && $_GET['setor'] !== '' ? (int)$_GET['setor'] : null;
$filtroStatus    = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;

$condicoes = [];
$parametros = [];

if ($filtroCategoria) {
    $condicoes[] = 'r.id_categoria = :categoria';
    $parametros[':categoria'] = $filtroCategoria;
}
if ($filtroSetor) {
    $condicoes[] = 'r.id_setor = :setor';
    $parametros[':setor'] = $filtroSetor;
}
if ($filtroStatus && in_array($filtroStatus, STATUS_RECURSO, true)) {
    $condicoes[] = 'r.status = :status';
    $parametros[':status'] = $filtroStatus;
}

$where = $condicoes ? 'WHERE ' . implode(' AND ', $condicoes) : '';

$sql = "SELECT r.id_recurso, r.nome, r.descricao, r.status, r.localizacao, r.foto,
               c.nome AS categoria_nome,
               s.nome AS setor_nome
        FROM recurso r
        INNER JOIN categoria c ON c.id_categoria = r.id_categoria
        INNER JOIN setor s ON s.id_setor = r.id_setor
        $where
        ORDER BY r.id_recurso DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$recursos = $stmt->fetchAll();

$categorias = $pdo->query("SELECT id_categoria, nome FROM categoria ORDER BY nome")->fetchAll();
$setores    = $pdo->query("SELECT id_setor, nome FROM setor ORDER BY nome")->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Recursos</h1>
        <p><?= count($recursos) ?> item(ns) encontrado(s)</p>
    </div>
    <a href="create.php" class="btn btn-primary">+ Novo recurso</a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <?php
        $mensagens = [
            'criado'     => 'Recurso criado com sucesso.',
            'atualizado' => 'Recurso atualizado com sucesso.',
            'excluido'   => 'Recurso excluído com sucesso.',
        ];
        $tipo = $_GET['msg'];
    ?>
    <?php if (isset($mensagens[$tipo])): ?>
        <div class="alert alert-success"><?= e($mensagens[$tipo]) ?></div>
    <?php elseif ($tipo === 'erro_fk'): ?>
        <div class="alert alert-danger">Não é possível excluir este recurso porque existem registros de histórico de uso vinculados a ele.</div>
    <?php endif; ?>
<?php endif; ?>

<form method="get" class="filter-bar">
    <select name="categoria" onchange="this.form.submit()">
        <option value="">Todas as categorias</option>
        <?php foreach ($categorias as $c): ?>
            <option value="<?= (int)$c['id_categoria'] ?>" <?= $filtroCategoria === (int)$c['id_categoria'] ? 'selected' : '' ?>>
                <?= e($c['nome']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="setor" onchange="this.form.submit()">
        <option value="">Todos os setores</option>
        <?php foreach ($setores as $s): ?>
            <option value="<?= (int)$s['id_setor'] ?>" <?= $filtroSetor === (int)$s['id_setor'] ? 'selected' : '' ?>>
                <?= e($s['nome']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="status" onchange="this.form.submit()">
        <option value="">Todos os status</option>
        <?php foreach (STATUS_RECURSO as $st): ?>
            <option value="<?= e($st) ?>" <?= $filtroStatus === $st ? 'selected' : '' ?>><?= e($st) ?></option>
        <?php endforeach; ?>
    </select>

    <?php if ($filtroCategoria || $filtroSetor || $filtroStatus): ?>
        <a href="index.php" class="btn btn-secondary btn-sm">Limpar filtros</a>
    <?php endif; ?>
</form>

<div class="card">
    <?php if (empty($recursos)): ?>
        <div class="empty-state">
            <strong>Nenhum recurso encontrado</strong>
            Ajuste os filtros ou cadastre um novo recurso.
        </div>
    <?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Foto</th>
                <th>Nome</th>
                <th>Categoria</th>
                <th>Setor</th>
                <th>Status</th>
                <th>Localização</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recursos as $r): ?>
            <tr>
                <td>
                    <?php if ($r['foto']): ?>
                        <img class="thumb" src="uploads/<?= e($r['foto']) ?>" alt="<?= e($r['nome']) ?>">
                    <?php else: ?>
                        <span class="thumb-placeholder">S/ foto</span>
                    <?php endif; ?>
                </td>
                <td>
                    <strong><?= e($r['nome']) ?></strong>
                    <?php if ($r['descricao']): ?>
                        <div style="color:var(--color-text-muted); font-size:12.5px; margin-top:2px;">
                            <?= e(mb_strimwidth($r['descricao'], 0, 60, '…')) ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td><span class="badge"><?= e($r['categoria_nome']) ?></span></td>
                <td><span class="badge badge-muted"><?= e($r['setor_nome']) ?></span></td>
                <td><span class="<?= statusBadgeClass($r['status']) ?>"><?= e($r['status']) ?></span></td>
                <td><?= $r['localizacao'] ? e($r['localizacao']) : '<span style="color:var(--color-text-muted)">—</span>' ?></td>
                <td>
                    <div class="table__actions">
                        <a class="btn btn-secondary btn-sm" href="edit.php?id=<?= (int)$r['id_recurso'] ?>">Editar</a>
                        <a class="btn btn-danger btn-sm" href="delete.php?id=<?= (int)$r['id_recurso'] ?>"
                           onclick="return confirm('Tem certeza que deseja excluir este recurso? Isso também apagará o histórico de uso relacionado.');">Excluir</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
