<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT foto FROM recursos WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $recurso = $stmt->fetch();

    if ($recurso) {
        $del = $pdo->prepare("DELETE FROM recursos WHERE id = :id");
        $del->execute([':id' => $id]);

        removerFoto($recurso['foto']);
    }
}

header('Location: index.php?msg=excluido');
exit;
