<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT foto FROM recurso WHERE id_recurso = :id");
    $stmt->execute([':id' => $id]);
    $recurso = $stmt->fetch();

    if ($recurso) {
        try {
            $del = $pdo->prepare("DELETE FROM recurso WHERE id_recurso = :id");
            $del->execute([':id' => $id]);

            removerFoto($recurso['foto']);

            header('Location: index.php?msg=excluido');
            exit;
        } catch (PDOException $e) {
            // A FK de historico_uso não tem ON DELETE CASCADE: se houver
            // histórico de uso vinculado a este recurso, a exclusão é bloqueada.
            header('Location: index.php?msg=erro_fk');
            exit;
        }
    }
}

header('Location: index.php');
exit;
