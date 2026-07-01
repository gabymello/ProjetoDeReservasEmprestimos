<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';

$pageTitle = 'Novo recurso';

$erros = [];
$dados = [
    'nome'         => '',
    'descricao'    => '',
    'id_categoria' => '',
    'id_setor'     => '',
    'status'       => 'Disponível',
    'localizacao'  => '',
];

$categorias = $pdo->query("SELECT id_categoria, nome FROM categoria ORDER BY nome")->fetchAll();
$setores    = $pdo->query("SELECT id_setor, nome FROM setor ORDER BY nome")->fetchAll();

if (empty($categorias) || empty($setores)) {
    $erros['geral'] = 'Cadastre ao menos uma categoria e um setor no banco antes de criar um recurso.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados['nome']         = trim($_POST['nome'] ?? '');
    $dados['descricao']    = trim($_POST['descricao'] ?? '');
    $dados['id_categoria'] = $_POST['id_categoria'] ?? '';
    $dados['id_setor']     = $_POST['id_setor'] ?? '';
    $dados['status']       = $_POST['status'] ?? 'Disponível';
    $dados['localizacao']  = trim($_POST['localizacao'] ?? '');

    if ($dados['nome'] === '') {
        $erros['nome'] = 'Informe o nome do recurso.';
    }
    if ($dados['id_categoria'] === '') {
        $erros['id_categoria'] = 'Selecione uma categoria.';
    }
    if ($dados['id_setor'] === '') {
        $erros['id_setor'] = 'Selecione um setor.';
    }
    if (!in_array($dados['status'], STATUS_RECURSO, true)) {
        $erros['status'] = 'Status inválido.';
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
            "INSERT INTO recurso (nome, descricao, id_categoria, id_setor, status, localizacao, foto)
             VALUES (:nome, :descricao, :id_categoria, :id_setor, :status, :localizacao, :foto)"
        );
        $stmt->execute([
            ':nome'         => $dados['nome'],
            ':descricao'    => $dados['descricao'] !== '' ? $dados['descricao'] : null,
            ':id_categoria' => (int)$dados['id_categoria'],
            ':id_setor'     => (int)$dados['id_setor'],
            ':status'       => $dados['status'],
            ':localizacao'  => $dados['localizacao'] !== '' ? $dados['localizacao'] : null,
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

<?php if (!empty($erros['geral'])): ?>
    <div class="alert alert-danger"><?= e($erros['geral']) ?></div>
<?php endif; ?>

<div class="form-card">
    <form method="post" enctype="multipart/form-data" novalidate>
        <div class="form-grid">
            <div class="form-group full">
                <label for="nome">Nome *</label>
                <input type="text" id="nome" name="nome" value="<?= e($dados['nome']) ?>" placeholder="Ex.: Projetor Epson PowerLite">
                <?php if (!empty($erros['nome'])): ?><div class="field-error"><?= e($erros['nome']) ?></div><?php endif; ?>
            </div>

            <div class="form-group full">
                <label for="descricao">Descrição <span class="opcional">(opcional)</span></label>
                <textarea id="descricao" name="descricao" placeholder="Detalhes adicionais sobre o recurso"><?= e($dados['descricao']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="id_categoria">Categoria *</label>
                <select id="id_categoria" name="id_categoria">
                    <option value="">Selecione...</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= (int)$c['id_categoria'] ?>" <?= (string)$dados['id_categoria'] === (string)$c['id_categoria'] ? 'selected' : '' ?>>
                            <?= e($c['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($erros['id_categoria'])): ?><div class="field-error"><?= e($erros['id_categoria']) ?></div><?php endif; ?>
            </div>

            <div class="form-group">
                <label for="id_setor">Setor *</label>
                <select id="id_setor" name="id_setor">
                    <option value="">Selecione...</option>
                    <?php foreach ($setores as $s): ?>
                        <option value="<?= (int)$s['id_setor'] ?>" <?= (string)$dados['id_setor'] === (string)$s['id_setor'] ? 'selected' : '' ?>>
                            <?= e($s['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($erros['id_setor'])): ?><div class="field-error"><?= e($erros['id_setor']) ?></div><?php endif; ?>
            </div>

            <div class="form-group">
                <label for="status">Status *</label>
                <select id="status" name="status">
                    <?php foreach (STATUS_RECURSO as $st): ?>
                        <option value="<?= e($st) ?>" <?= $dados['status'] === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="localizacao">Localização <span class="opcional">(opcional)</span></label>
                <input type="text" id="localizacao" name="localizacao" value="<?= e($dados['localizacao']) ?>" placeholder="Ex.: Sala 12, Bloco B">
            </div>

            <div class="form-group full">
                <label for="foto">Foto <span class="opcional">(opcional)</span></label>
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
