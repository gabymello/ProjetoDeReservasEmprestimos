<?php
if (!defined('APP_RUNNING')) {
    exit;
}
?>
<h2>Registrar emprestimo manual</h2>
<form method="post" class="item-form">
    <input type="hidden" name="action" value="request_loan">
    <label>Item / Sala
        <select name="item_id" required>
            <option value="">Selecione</option>
            <?php foreach ($items as $item): ?>
                <?php if ($item['status'] === 'Disponivel'): ?>
                    <option value="<?= (int) $item['id'] ?>"><?= e($item['name']) ?> - <?= e($item['location']) ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Quem pegou
        <input name="borrower_name" required placeholder="Nome do aluno, professor ou funcionario">
    </label>
    <label>Data que pegou
        <input type="date" name="pickup_date" value="<?= date('Y-m-d') ?>" required>
    </label>
    <label>Previsao de devolucao
        <input type="date" name="expected_return">
    </label>
    <label>Com quem pegou
        <input name="received_by" required placeholder="Nome do responsavel pela entrega">
    </label>
    <label>Observacoes
        <input name="notes" placeholder="Ex: retirou com cabo HDMI">
    </label>
    <button type="submit">Registrar Emprestimo</button>
</form>
