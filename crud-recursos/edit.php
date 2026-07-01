<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';

$pageTitle = 'Editar recurso';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM recursos WHERE id = :id");
$stmt->execute([':id' => $id]);
$recurso = $stmt->fetch();

if (!$recurso) {
    header('Location: index.php');
    exit;
}

$categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome")->fetchAll();
$setores    = $pdo->query("SELECT id, nome FROM setores ORDER BY nome")->fetchAll();

$erros = [];
$dados = [
    'nome'         => $recurso['nome'],
    'descricao'    => $recurso['descricao'],
    'categoria_id' => $recurso['categoria_id'],
    'setor_id'     => $recurso['setor_id'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados['nome']         = trim($_POST['nome'] ?? '');
    $dados['descricao']    = trim($_POST['descricao'] ?? '');
    $dados['categoria_id'] = $_POST['categoria_id'] !== '' ? (int)$_POST['categoria_id'] : null;
    $dados['setor_id']     = $_POST['setor_id'] !== '' ? (int)$_POST['setor_id'] : null;

    if ($dados['nome'] === '') {
        $erros['nome'] = 'Informe o nome do recurso.';
    }

    $removerFotoAtual = isset($_POST['remover_foto']);
    $nomeFoto = $recurso['foto'];

    if ($removerFotoAtual) {
        removerFoto($recurso['foto']);
        $nomeFoto = null;
    }

    if (!empty($_FILES['foto']['name'])) {
        $upload = uploadFoto($_FILES['foto'], $nomeFoto);
        if (!$upload['ok']) {
            $erros['foto'] = $upload['error'];
        } else {
            $nomeFoto = $upload['filename'];
        }
    }

    if (empty($erros)) {
        $stmt = $pdo->prepare(
            "UPDATE recursos
             SET nome = :nome, descricao = :descricao, categoria_id = :categoria_id,
                 setor_id = :setor_id, foto = :foto
             WHERE id = :id"
        );
        $stmt->execute([
            ':nome'         => $dados['nome'],
            ':descricao'    => $dados['descricao'] !== '' ? $dados['descricao'] : null,
            ':categoria_id' => $dados['categoria_id'],
            ':setor_id'     => $dados['setor_id'],
            ':foto'         => $nomeFoto,
            ':id'           => $id,
        ]);

        header('Location: index.php?msg=atualizado');
        exit;
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Editar recurso</h1>
        <p>Atualize as informações do recurso selecionado.</p>
    </div>
    <a href="index.php" class="btn btn-secondary">Voltar</a>
</div>

<div class="form-card">
    <form method="post" enctype="multipart/form-data" novalidate>
        <div class="form-grid">
            <div class="form-group full">
                <label for="nome">Nome *</label>
                <input type="text" id="nome" name="nome" value="<?= e($dados['nome']) ?>">
                <?php if (!empty($erros['nome'])): ?><div class="field-error"><?= e($erros['nome']) ?></div><?php endif; ?>
            </div>

            <div class="form-group full">
                <label for="descricao">Descrição</label>
                <textarea id="descricao" name="descricao"><?= e($dados['descricao']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="categoria_id">Categoria</label>
                <select id="categoria_id" name="categoria_id">
                    <option value="">Não definida</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= (string)$dados['categoria_id'] === (string)$c['id'] ? 'selected' : '' ?>>
                            <?= e($c['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="setor_id">Setor</label>
                <select id="setor_id" name="setor_id">
                    <option value="">Não definido</option>
                    <?php foreach ($setores as $s): ?>
                        <option value="<?= (int)$s['id'] ?>" <?= (string)$dados['setor_id'] === (string)$s['id'] ? 'selected' : '' ?>>
                            <?= e($s['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group full">
                <label for="foto">Foto</label>

                <?php if ($recurso['foto']): ?>
                    <div class="foto-preview-atual">
                        <img src="uploads/<?= e($recurso['foto']) ?>" alt="Foto atual">
                        <span>Foto atual</span>
                        <label style="margin-left:auto; display:flex; align-items:center; gap:6px; font-weight:400; font-size:12.5px;">
                            <input type="checkbox" name="remover_foto" value="1" style="width:auto;"> Remover foto
                        </label>
                    </div>
                <?php endif; ?>

                <input type="file" id="foto" name="foto" accept="image/*">
                <div class="hint">Envie um novo arquivo apenas se quiser substituir a foto atual. JPG, PNG, WEBP ou GIF — máximo 5MB.</div>
                <?php if (!empty($erros['foto'])): ?><div class="field-error"><?= e($erros['foto']) ?></div><?php endif; ?>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Salvar alterações</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
