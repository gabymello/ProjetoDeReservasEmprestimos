<?php
if (!defined('APP_RUNNING')) {
    exit;
}

$rows = $visibleLoans ?? $loans;
usort($rows, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Item / Sala</th>
                <th>Quem pegou</th>
                <th>Data</th>
                <th>Com quem pegou</th>
                <th>Status</th>
                <?php if (is_admin()): ?><th>Acoes</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="<?= is_admin() ? 6 : 5 ?>">Nenhum registro encontrado.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $loan): ?>
                <?php $item = find_by_id($items, (int) $loan['item_id']); ?>
                <tr>
                    <td><?= e($item['name'] ?? 'Item removido') ?></td>
                    <td><?= e($loan['borrower_name'] ?? '') ?></td>
                    <td>
                        <?= e(date('d/m/Y', strtotime($loan['pickup_date'] ?? date('Y-m-d')))) ?>
                        <?php if (!empty($loan['expected_return'])): ?>
                            <small>Devolver: <?= e(date('d/m/Y', strtotime($loan['expected_return']))) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= e($loan['received_by'] ?: 'Nao informado') ?></td>
                    <td><span class="badge <?= loan_status_class($loan['status']) ?>"><?= e($loan['status']) ?></span></td>
                    <?php if (is_admin()): ?>
                        <td class="row-actions">
                            <?php if ($loan['status'] === 'Pendente'): ?>
                                <form method="post">
                                    <input type="hidden" name="action" value="reserve_loan">
                                    <input type="hidden" name="id" value="<?= (int) $loan['id'] ?>">
                                    <button type="submit">Permitir Reserva</button>
                                </form>
                                <form method="post">
                                    <input type="hidden" name="action" value="reject_loan">
                                    <input type="hidden" name="id" value="<?= (int) $loan['id'] ?>">
                                    <button type="submit" class="danger-button">Recusar</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($loan['status'] === 'Reservado'): ?>
                                <form method="post">
                                    <input type="hidden" name="action" value="deliver_loan">
                                    <input type="hidden" name="id" value="<?= (int) $loan['id'] ?>">
                                    <button type="submit">Registrar Retirada</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($loan['status'] === 'Em uso'): ?>
                                <form method="post">
                                    <input type="hidden" name="action" value="return_loan">
                                    <input type="hidden" name="id" value="<?= (int) $loan['id'] ?>">
                                    <button type="submit" class="secondary">Devolver</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
