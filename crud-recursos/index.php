<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';

$pageTitle = 'Recursos';

$sql = "SELECT r.id, r.nome, r.descricao, r.foto,
               c.nome AS categoria_nome,
               s.nome AS setor_nome
        FROM recursos r
        LEFT JOIN categorias c ON c.id = r.categoria_id
        LEFT JOIN setores s ON s.id = r.setor_id
        ORDER BY r.id DESC";

$recursos = $pdo->query($sql)->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Recursos</h1>
        <p><?= count($recursos) ?> item(ns) cadastrado(s)</p>
    </div>
    <a href="create.php" class="btn btn-primary">+ Novo recurso</a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <?php
        $mensagens = [
            'criado'    => 'Recurso criado com sucesso.',
            'atualizado'=> 'Recurso atualizado com sucesso.',
            'excluido'  => 'Recurso excluído com sucesso.',
        ];
        $tipo = $_GET['msg'];
    ?>
    <?php if (isset($mensagens[$tipo])): ?>
        <div class="alert alert-success"><?= e($mensagens[$tipo]) ?></div>
    <?php endif; ?>
<?php endif; ?>

<div class="card">
    <?php if (empty($recursos)): ?>
        <div class="empty-state">
            <strong>Nenhum recurso cadastrado</strong>
            Comece adicionando o primeiro recurso da sua lista.
        </div>
    <?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Foto</th>
                <th>Nome</th>
                <th>Categoria</th>
                <th>Setor</th>
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
                            <?= e(mb_strimwidth($r['descricao'], 0, 70, '…')) ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($r['categoria_nome']): ?>
                        <span class="badge"><?= e($r['categoria_nome']) ?></span>
                    <?php else: ?>
                        <span class="badge badge-muted">Não definida</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($r['setor_nome']): ?>
                        <span class="badge"><?= e($r['setor_nome']) ?></span>
                    <?php else: ?>
                        <span class="badge badge-muted">Não definido</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="table__actions">
                        <a class="btn btn-secondary btn-sm" href="edit.php?id=<?= (int)$r['id'] ?>">Editar</a>
                        <a class="btn btn-danger btn-sm" href="delete.php?id=<?= (int)$r['id'] ?>"
                           onclick="return confirm('Tem certeza que deseja excluir este recurso?');">Excluir</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
