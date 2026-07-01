<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';

$pageTitle = 'Novo recurso';

$erros = [];
$dados = ['nome' => '', 'descricao' => '', 'categoria_id' => '', 'setor_id' => ''];

$categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome")->fetchAll();
$setores    = $pdo->query("SELECT id, nome FROM setores ORDER BY nome")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados['nome']         = trim($_POST['nome'] ?? '');
    $dados['descricao']    = trim($_POST['descricao'] ?? '');
    $dados['categoria_id'] = $_POST['categoria_id'] !== '' ? (int)$_POST['categoria_id'] : null;
    $dados['setor_id']     = $_POST['setor_id'] !== '' ? (int)$_POST['setor_id'] : null;

    if ($dados['nome'] === '') {
        $erros['nome'] = 'Informe o nome do recurso.';
    }

    $upload = ['ok' => true, 'filename' => null, 'error' => null];
    if (!empty($_FILES['foto']['name'])) {
        $upload = uploadFoto($_FILES['foto']);
        if (!$upload['ok']) {
            $erros['foto'] = $upload['error'];
        }
    }

    if (empty($erros)) {
        $stmt = $pdo->prepare(
            "INSERT INTO recursos (nome, descricao, categoria_id, setor_id, foto)
             VALUES (:nome, :descricao, :categoria_id, :setor_id, :foto)"
        );
        $stmt->execute([
            ':nome'         => $dados['nome'],
            ':descricao'    => $dados['descricao'] !== '' ? $dados['descricao'] : null,
            ':categoria_id' => $dados['categoria_id'],
            ':setor_id'     => $dados['setor_id'],
            ':foto'         => $upload['filename'],
        ]);

        header('Location: index.php?msg=criado');
        exit;
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Novo recurso</h1>
        <p>Preencha as informações abaixo para cadastrar um recurso.</p>
    </div>
    <a href="index.php" class="btn btn-secondary">Voltar</a>
</div>

<div class="form-card">
    <form method="post" enctype="multipart/form-data" novalidate>
        <div class="form-grid">
            <div class="form-group full">
                <label for="nome">Nome *</label>
                <input type="text" id="nome" name="nome" value="<?= e($dados['nome']) ?>" placeholder="Ex.: Notebook Dell Latitude">
                <?php if (!empty($erros['nome'])): ?><div class="field-error"><?= e($erros['nome']) ?></div><?php endif; ?>
            </div>

            <div class="form-group full">
                <label for="descricao">Descrição</label>
                <textarea id="descricao" name="descricao" placeholder="Detalhes adicionais sobre o recurso (opcional)"><?= e($dados['descricao']) ?></textarea>
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
                <div class="hint">Cadastrada na tabela "categorias".</div>
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
                <div class="hint">Cadastrado na tabela "setores".</div>
            </div>

            <div class="form-group full">
                <label for="foto">Foto</label>
                <input type="file" id="foto" name="foto" accept="image/*">
                <div class="hint">JPG, PNG, WEBP ou GIF — máximo 5MB.</div>
                <?php if (!empty($erros['foto'])): ?><div class="field-error"><?= e($erros['foto']) ?></div><?php endif; ?>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Salvar recurso</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
