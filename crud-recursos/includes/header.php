<?php if (!isset($pageTitle)) { $pageTitle = 'Recursos'; } ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> · Gestão de Recursos</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="layout">
    <header class="topbar">
        <div class="topbar__brand">
            <span class="topbar__logo">GR</span>
            <span class="topbar__title">Gestão de Recursos</span>
        </div>
        <nav class="topbar__nav">
            <a href="index.php">Recursos</a>
            <a href="api/recursos.php" target="_blank" rel="noopener">API JSON</a>
        </nav>
    </header>

    <main class="content">
