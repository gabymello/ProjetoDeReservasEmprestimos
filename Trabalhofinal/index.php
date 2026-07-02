<?php
define('APP_RUNNING', true);

const DATA_DIR = __DIR__ . '/data';
const UPLOAD_DIR = __DIR__ . '/uploads';
const USERS_FILE = DATA_DIR . '/users.json';
const ITEMS_FILE = DATA_DIR . '/items.json';
const LOANS_FILE = DATA_DIR . '/loans.json';

date_default_timezone_set('America/Sao_Paulo');

if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0777, true);
}

if (!is_dir(DATA_DIR . '/sessions')) {
    mkdir(DATA_DIR . '/sessions', 0777, true);
}

session_save_path(DATA_DIR . '/sessions');
session_start();

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

function read_json(string $file, array $fallback): array
{
    if (!file_exists($file)) {
        file_put_contents($file, json_encode($fallback, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $fallback;
    }

    $contents = file_get_contents($file);
    $decoded = json_decode($contents, true);
    return is_array($decoded) ? $decoded : $fallback;
}

function save_json(string $file, array $data): void
{
    file_put_contents($file, json_encode(array_values($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function next_id(array $rows): int
{
    $ids = array_column($rows, 'id');
    return $ids ? max($ids) + 1 : 1;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        header('Location: ?page=login');
        exit;
    }
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        header('Location: ?page=viewer');
        exit;
    }
}

function redirect_to(string $page): void
{
    header("Location: ?page={$page}");
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function find_by_id(array $rows, int $id): ?array
{
    foreach ($rows as $row) {
        if ((int) $row['id'] === $id) {
            return $row;
        }
    }

    return null;
}

function uploaded_image_path(string $field, ?string $current = null): ?string
{
    if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return $current;
    }

    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $extension = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowed, true)) {
        return $current;
    }

    $filename = uniqid('item_', true) . '.' . $extension;
    $target = UPLOAD_DIR . '/' . $filename;

    if (move_uploaded_file($_FILES[$field]['tmp_name'], $target)) {
        return 'uploads/' . $filename;
    }

    return $current;
}

function placeholder_image(string $name, string $category): string
{
    $label = strtoupper(substr($name, 0, 2));
    $icon = match (strtolower($category)) {
        'equipamentos' => 'Projetor',
        'informatica' => 'Notebook',
        'audio' => 'Audio',
        'salas' => 'Sala',
        'laboratorios' => 'Lab',
        default => 'Item',
    };

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="520" viewBox="0 0 900 520">'
        . '<defs><linearGradient id="g" x1="0" x2="1"><stop stop-color="#eef5ff"/><stop offset="1" stop-color="#e8f7f0"/></linearGradient></defs>'
        . '<rect width="900" height="520" fill="url(#g)"/>'
        . '<rect x="245" y="115" width="410" height="235" rx="24" fill="#ffffff" stroke="#d7e2f0" stroke-width="8"/>'
        . '<circle cx="450" cy="232" r="62" fill="#0f67c7" opacity=".92"/>'
        . '<text x="450" y="244" text-anchor="middle" font-family="Arial" font-size="48" font-weight="700" fill="#fff">' . e($label) . '</text>'
        . '<text x="450" y="405" text-anchor="middle" font-family="Arial" font-size="42" font-weight="700" fill="#0b2545">' . e($icon) . '</text>'
        . '</svg>';

    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

$defaultUsers = [
    [
        'id' => 1,
        'name' => 'Administrador',
        'email' => 'admin@escola.com',
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'role' => 'admin',
    ],
    [
        'id' => 2,
        'name' => 'Visualizador',
        'email' => 'aluno@escola.com',
        'password' => password_hash('aluno123', PASSWORD_DEFAULT),
        'role' => 'viewer',
    ],
];

$defaultItems = [
    ['id' => 1, 'name' => 'Projetor Multimidia', 'category' => 'Equipamentos', 'location' => 'Sala 201 - Bloco A', 'description' => 'Projetor Epson PowerLite X41+', 'status' => 'Disponivel', 'image' => null],
    ['id' => 2, 'name' => 'Notebook Dell', 'category' => 'Informatica', 'location' => 'Sala 203 - Bloco A', 'description' => 'Notebook Dell Inspiron 15', 'status' => 'Disponivel', 'image' => null],
    ['id' => 3, 'name' => 'Caixa de Som', 'category' => 'Audio', 'location' => 'Auditorio', 'description' => 'Caixa de som amplificada', 'status' => 'Disponivel', 'image' => null],
    ['id' => 4, 'name' => 'Sala 101', 'category' => 'Salas', 'location' => 'Bloco A - 1 Andar', 'description' => 'Sala de aula com capacidade para 30 alunos', 'status' => 'Disponivel', 'image' => null],
    ['id' => 5, 'name' => 'Laboratorio de Informatica', 'category' => 'Laboratorios', 'location' => 'Bloco B - 2 Andar', 'description' => 'Laboratorio com 20 computadores', 'status' => 'Disponivel', 'image' => null],
    ['id' => 6, 'name' => 'Microfone Sem Fio', 'category' => 'Audio', 'location' => 'Auditorio', 'description' => 'Microfone sem fio profissional', 'status' => 'Disponivel', 'image' => null],
];

$users = read_json(USERS_FILE, $defaultUsers);
$items = read_json(ITEMS_FILE, $defaultItems);
$loans = read_json(LOANS_FILE, []);
$page = $_GET['page'] ?? (current_user() ? (is_admin() ? 'dashboard' : 'viewer') : 'login');
$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        foreach ($users as $user) {
            if ($user['email'] === $email && password_verify($password, $user['password'])) {
                $_SESSION['user'] = ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']];
                redirect_to($user['role'] === 'admin' ? 'dashboard' : 'viewer');
            }
        }

        $_SESSION['message'] = 'E-mail ou senha incorretos.';
        redirect_to('login');
    }

    if ($action === 'logout') {
        session_destroy();
        redirect_to('login');
    }

    if ($action === 'save_item') {
        require_admin();
        $id = (int) ($_POST['id'] ?? 0);
        $image = uploaded_image_path('image');
        $row = [
            'id' => $id ?: next_id($items),
            'name' => trim($_POST['name'] ?? ''),
            'category' => trim($_POST['category'] ?? ''),
            'location' => trim($_POST['location'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'status' => $_POST['status'] ?? 'Disponivel',
            'image' => $image,
        ];

        if ($id) {
            foreach ($items as $index => $item) {
                if ((int) $item['id'] === $id) {
                    $row['image'] = uploaded_image_path('image', $item['image'] ?? null);
                    $items[$index] = $row;
                }
            }
        } else {
            $items[] = $row;
        }

        save_json(ITEMS_FILE, $items);
        $_SESSION['message'] = 'Item salvo com sucesso.';
        redirect_to('items');
    }

    if ($action === 'delete_item') {
        require_admin();
        $id = (int) ($_POST['id'] ?? 0);
        $items = array_values(array_filter($items, fn($item) => (int) $item['id'] !== $id));
        save_json(ITEMS_FILE, $items);
        $_SESSION['message'] = 'Item removido.';
        redirect_to('items');
    }

    if ($action === 'request_loan') {
        require_login();
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $item = find_by_id($items, $itemId);

        if ($item && ($item['status'] ?? '') === 'Disponivel') {
            $loans[] = [
                'id' => next_id($loans),
                'item_id' => $itemId,
                'user_id' => current_user()['id'],
                'borrower_name' => trim($_POST['borrower_name'] ?? current_user()['name']),
                'pickup_date' => $_POST['pickup_date'] ?? date('Y-m-d'),
                'expected_return' => $_POST['expected_return'] ?? '',
                'received_by' => trim($_POST['received_by'] ?? ''),
                'status' => is_admin() ? 'Em uso' : 'Pendente',
                'notes' => trim($_POST['notes'] ?? ''),
                'created_at' => date('Y-m-d H:i:s'),
                'returned_at' => null,
            ];

            if (is_admin()) {
                foreach ($items as $index => $current) {
                    if ((int) $current['id'] === $itemId) {
                        $items[$index]['status'] = 'Em Uso';
                    }
                }
                save_json(ITEMS_FILE, $items);
            }

            save_json(LOANS_FILE, $loans);
            $_SESSION['message'] = is_admin() ? 'Empréstimo registrado.' : 'Solicitação enviada para o administrador.';
        }

        redirect_to(is_admin() ? 'loans' : 'my-loans');
    }

    if ($action === 'reserve_loan') {
        require_admin();
        $loanId = (int) ($_POST['id'] ?? 0);
        $approved = false;
        foreach ($loans as $index => $loan) {
            if ((int) $loan['id'] === $loanId && $loan['status'] === 'Pendente') {
                foreach ($items as $itemIndex => $item) {
                    if ((int) $item['id'] === (int) $loan['item_id']) {
                        if (($item['status'] ?? '') !== 'Disponivel') {
                            $_SESSION['message'] = 'Nao foi possivel aprovar: este item nao esta disponivel.';
                            redirect_to('loans');
                        }

                        $items[$itemIndex]['status'] = 'Reservado';
                        $loans[$index]['status'] = 'Reservado';
                        $loans[$index]['approved_by'] = current_user()['name'];
                        $loans[$index]['approved_at'] = date('Y-m-d H:i:s');
                        $approved = true;
                    }
                }
            }
        }
        save_json(LOANS_FILE, $loans);
        save_json(ITEMS_FILE, $items);
        $_SESSION['message'] = $approved ? 'Reserva aprovada pelo administrador.' : 'Solicitacao nao encontrada.';
        redirect_to('loans');
    }

    if ($action === 'reject_loan') {
        require_admin();
        $loanId = (int) ($_POST['id'] ?? 0);
        foreach ($loans as $index => $loan) {
            if ((int) $loan['id'] === $loanId && $loan['status'] === 'Pendente') {
                $loans[$index]['status'] = 'Recusado';
                $loans[$index]['rejected_by'] = current_user()['name'];
                $loans[$index]['rejected_at'] = date('Y-m-d H:i:s');
            }
        }
        save_json(LOANS_FILE, $loans);
        $_SESSION['message'] = 'Solicitacao recusada.';
        redirect_to('loans');
    }

    if ($action === 'deliver_loan') {
        require_admin();
        $loanId = (int) ($_POST['id'] ?? 0);
        foreach ($loans as $index => $loan) {
            if ((int) $loan['id'] === $loanId && $loan['status'] === 'Reservado') {
                $loans[$index]['status'] = 'Em uso';
                $loans[$index]['delivered_by'] = current_user()['name'];
                $loans[$index]['delivered_at'] = date('Y-m-d H:i:s');
                foreach ($items as $itemIndex => $item) {
                    if ((int) $item['id'] === (int) $loan['item_id']) {
                        $items[$itemIndex]['status'] = 'Em Uso';
                    }
                }
            }
        }
        save_json(LOANS_FILE, $loans);
        save_json(ITEMS_FILE, $items);
        $_SESSION['message'] = 'Retirada registrada. Agora o item esta em uso.';
        redirect_to('loans');
    }

    if ($action === 'approve_loan') {
        require_admin();
        $loanId = (int) ($_POST['id'] ?? 0);
        foreach ($loans as $index => $loan) {
            if ((int) $loan['id'] === $loanId) {
                $loans[$index]['status'] = 'Em uso';
                foreach ($items as $itemIndex => $item) {
                    if ((int) $item['id'] === (int) $loan['item_id']) {
                        $items[$itemIndex]['status'] = 'Em Uso';
                    }
                }
            }
        }
        save_json(LOANS_FILE, $loans);
        save_json(ITEMS_FILE, $items);
        $_SESSION['message'] = 'Empréstimo aprovado.';
        redirect_to('loans');
    }

    if ($action === 'return_loan') {
        require_admin();
        $loanId = (int) ($_POST['id'] ?? 0);
        foreach ($loans as $index => $loan) {
            if ((int) $loan['id'] === $loanId) {
                $loans[$index]['status'] = 'Devolvido';
                $loans[$index]['returned_at'] = date('Y-m-d H:i:s');
                foreach ($items as $itemIndex => $item) {
                    if ((int) $item['id'] === (int) $loan['item_id']) {
                        $items[$itemIndex]['status'] = 'Disponivel';
                    }
                }
            }
        }
        save_json(LOANS_FILE, $loans);
        save_json(ITEMS_FILE, $items);
        $_SESSION['message'] = 'Devolução registrada.';
        redirect_to('loans');
    }
}

function filtered_items(array $items): array
{
    $search = strtolower(trim($_GET['q'] ?? ''));
    $category = $_GET['category'] ?? '';
    $status = $_GET['status'] ?? '';

    return array_values(array_filter($items, function ($item) use ($search, $category, $status) {
        $matchesSearch = !$search || str_contains(strtolower($item['name'] . ' ' . $item['description'] . ' ' . $item['location']), $search);
        $matchesCategory = !$category || $item['category'] === $category;
        $matchesStatus = !$status || $item['status'] === $status;
        return $matchesSearch && $matchesCategory && $matchesStatus;
    }));
}

function item_status_class(string $status): string
{
    return match ($status) {
        'Reservado' => 'primary',
        'Em Uso' => 'warning',
        'Manutencao' => 'danger',
        default => 'success',
    };
}

function loan_status_class(string $status): string
{
    return match ($status) {
        'Pendente' => 'warning',
        'Reservado' => 'primary',
        'Recusado' => 'danger',
        'Devolvido' => 'muted',
        default => 'primary',
    };
}

function render_sidebar(string $active): void
{
    $admin = is_admin();
    ?>
    <aside class="sidebar">
        <div class="brand">
            <span class="brand-icon">SR</span>
            <strong>Sistema de Reservas</strong>
        </div>
        <nav>
            <?php if ($admin): ?>
                <a class="<?= $active === 'dashboard' ? 'active' : '' ?>" href="?page=dashboard">Dashboard</a>
                <a class="<?= $active === 'loans' ? 'active' : '' ?>" href="?page=loans">Reservas e Emprestimos</a>
                <a class="<?= $active === 'items' ? 'active' : '' ?>" href="?page=items">Itens / Salas</a>
            <?php endif; ?>
            <a class="<?= $active === 'viewer' ? 'active' : '' ?>" href="?page=viewer">Visualizador</a>
            <a class="<?= $active === 'my-loans' ? 'active' : '' ?>" href="?page=my-loans">Meus Emprestimos</a>
        </nav>
        <form method="post" class="logout-form">
            <input type="hidden" name="action" value="logout">
            <button type="submit">Sair</button>
        </form>
    </aside>
    <?php
}

function render_header(string $title, string $subtitle): void
{
    ?>
    <header class="topbar">
        <div>
            <h1><?= e($title) ?></h1>
            <p><?= e($subtitle) ?></p>
        </div>
        <div class="user-chip">
            <span><?= e(current_user()['name']) ?></span>
            <small><?= is_admin() ? 'Administrador' : 'Visualizador' ?></small>
        </div>
    </header>
    <?php
}

function render_item_card(array $item, bool $showForm): void
{
    $image = $item['image'] ?: placeholder_image($item['name'], $item['category']);
    ?>
    <article class="item-card">
        <div class="item-photo">
            <img src="<?= e($image) ?>" alt="<?= e($item['name']) ?>">
            <span class="badge <?= item_status_class($item['status']) ?>"><?= e($item['status']) ?></span>
        </div>
        <div class="item-body">
            <h3><?= e($item['name']) ?></h3>
            <p><strong>Categoria:</strong> <?= e($item['category']) ?></p>
            <p><strong>Local:</strong> <?= e($item['location']) ?></p>
            <p><?= e($item['description']) ?></p>
            <?php if ($showForm && $item['status'] === 'Disponivel'): ?>
                <details class="loan-details">
                    <summary>Solicitar / Registrar</summary>
                    <form method="post" class="compact-form">
                        <input type="hidden" name="action" value="request_loan">
                        <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                        <label>Nome de quem pegou
                            <input name="borrower_name" value="<?= e(current_user()['name']) ?>" required>
                        </label>
                        <label>Data que pegou
                            <input type="date" name="pickup_date" value="<?= date('Y-m-d') ?>" required>
                        </label>
                        <label>Previsao de devolucao
                            <input type="date" name="expected_return">
                        </label>
                        <label>Com quem pegou
                            <input name="received_by" placeholder="Ex: Secretaria, professor, ADM">
                        </label>
                        <button type="submit"><?= is_admin() ? 'Registrar Emprestimo' : 'Enviar Solicitacao' ?></button>
                    </form>
                </details>
            <?php else: ?>
                <button class="secondary" disabled>Indisponivel para emprestimo</button>
            <?php endif; ?>
        </div>
    </article>
    <?php
}

if ($page !== 'login') {
    require_login();
}

if (in_array($page, ['dashboard', 'items', 'loans'], true)) {
    require_admin();
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema de Reservas e Emprestimos</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php if ($page === 'login'): ?>
    <main class="login-screen">
        <section class="login-panel">
            <div class="login-copy">
                <span class="brand-icon large">SR</span>
                <h1>Sistema de Reservas</h1>
                <p>Controle de itens, salas, responsaveis, datas de retirada e devolucao.</p>
            </div>
            <form method="post" class="login-form">
                <input type="hidden" name="action" value="login">
                <h2>Entrar</h2>
                <?php if ($message): ?><div class="alert"><?= e($message) ?></div><?php endif; ?>
                <label>E-mail
                    <input type="email" name="email" required placeholder="admin@escola.com">
                </label>
                <label>Senha
                    <input type="password" name="password" required placeholder="admin123">
                </label>
                <button type="submit">Acessar</button>
                <div class="demo-users">
                    <strong>Usuarios de teste</strong>
                    <span>Admin: admin@escola.com / admin123</span>
                    <span>Visualizador: aluno@escola.com / aluno123</span>
                </div>
            </form>
        </section>
    </main>
<?php else: ?>
    <div class="app-shell">
        <?php render_sidebar($page); ?>
        <main class="content">
            <?php if ($message): ?><div class="alert success-alert"><?= e($message) ?></div><?php endif; ?>

            <?php if ($page === 'dashboard'): ?>
                <?php
                $activeLoans = array_filter($loans, fn($loan) => $loan['status'] === 'Em uso');
                $pendingLoans = array_filter($loans, fn($loan) => $loan['status'] === 'Pendente');
                $reservedLoans = array_filter($loans, fn($loan) => $loan['status'] === 'Reservado');
                $availableItems = array_filter($items, fn($item) => $item['status'] === 'Disponivel');
                render_header('Dashboard', 'Resumo geral das reservas, emprestimos e disponibilidade.');
                ?>
                <section class="stats-grid">
                    <div class="stat-card"><span><?= count($items) ?></span><p>Itens cadastrados</p></div>
                    <div class="stat-card"><span><?= count($availableItems) ?></span><p>Disponiveis</p></div>
                    <div class="stat-card"><span><?= count($pendingLoans) ?></span><p>Pendentes</p></div>
                    <div class="stat-card"><span><?= count($reservedLoans) ?></span><p>Reservados</p></div>
                    <div class="stat-card"><span><?= count($activeLoans) ?></span><p>Em uso</p></div>
                </section>
                <section class="panel">
                    <div class="panel-title">
                        <h2>Ultimos registros</h2>
                        <a href="?page=loans">Ver todos</a>
                    </div>
                    <?php include __DIR__ . '/index.php.table.php'; ?>
                </section>
            <?php endif; ?>

            <?php if ($page === 'viewer'): ?>
                <?php render_header('Visualizador', 'Veja, filtre e solicite os itens ou salas disponiveis.'); ?>
                <section class="panel">
                    <h2>Filtro de Busca Rapida</h2>
                    <form method="get" class="filters">
                        <input type="hidden" name="page" value="viewer">
                        <label>Buscar
                            <input name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Buscar por nome do item ou sala...">
                        </label>
                        <label>Categoria
                            <select name="category">
                                <option value="">Todas</option>
                                <?php foreach (array_unique(array_column($items, 'category')) as $category): ?>
                                    <option value="<?= e($category) ?>" <?= ($_GET['category'] ?? '') === $category ? 'selected' : '' ?>><?= e($category) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Status
                            <select name="status">
                                <option value="">Todos</option>
                                <?php foreach (['Disponivel', 'Reservado', 'Em Uso', 'Manutencao'] as $status): ?>
                                    <option value="<?= e($status) ?>" <?= ($_GET['status'] ?? '') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <button type="submit">Buscar</button>
                    </form>
                </section>
                <section class="panel">
                    <h2>Itens / Salas Encontrados</h2>
                    <div class="cards-grid">
                        <?php foreach (filtered_items($items) as $item): ?>
                            <?php render_item_card($item, true); ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($page === 'my-loans'): ?>
                <?php render_header('Meus Emprestimos', 'Acompanhe o que voce solicitou ou retirou.'); ?>
                <section class="panel">
                    <?php
                    $visibleLoans = array_values(array_filter($loans, fn($loan) => (int) $loan['user_id'] === (int) current_user()['id']));
                    include __DIR__ . '/index.php.table.php';
                    ?>
                </section>
            <?php endif; ?>

            <?php if ($page === 'loans'): ?>
                <?php render_header('Reservas e Emprestimos', 'Controle quem pegou, quando pegou e com quem retirou.'); ?>
                <section class="panel">
                    <?php include __DIR__ . '/index.php.loan-form.php'; ?>
                </section>
                <section class="panel">
                    <h2>Historico e Emprestimos Ativos</h2>
                    <?php include __DIR__ . '/index.php.table.php'; ?>
                </section>
            <?php endif; ?>

            <?php if ($page === 'items'): ?>
                <?php
                $editing = isset($_GET['edit']) ? find_by_id($items, (int) $_GET['edit']) : null;
                render_header('Itens / Salas', 'Cadastre fotos, locais, categorias e disponibilidade.');
                ?>
                <section class="panel">
                    <h2><?= $editing ? 'Editar item' : 'Novo item ou sala' ?></h2>
                    <form method="post" enctype="multipart/form-data" class="item-form">
                        <input type="hidden" name="action" value="save_item">
                        <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
                        <label>Nome
                            <input name="name" value="<?= e($editing['name'] ?? '') ?>" required>
                        </label>
                        <label>Categoria
                            <input name="category" value="<?= e($editing['category'] ?? '') ?>" placeholder="Equipamentos, Audio, Salas..." required>
                        </label>
                        <label>Local
                            <input name="location" value="<?= e($editing['location'] ?? '') ?>" required>
                        </label>
                        <label>Status
                            <select name="status">
                                <?php foreach (['Disponivel', 'Reservado', 'Em Uso', 'Manutencao'] as $status): ?>
                                    <option value="<?= e($status) ?>" <?= ($editing['status'] ?? '') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="wide">Descricao
                            <textarea name="description" rows="3"><?= e($editing['description'] ?? '') ?></textarea>
                        </label>
                        <label class="wide">Foto do item ou sala
                            <input type="file" name="image" accept="image/*">
                        </label>
                        <button type="submit">Salvar Item</button>
                    </form>
                </section>
                <section class="panel">
                    <h2>Itens cadastrados</h2>
                    <div class="cards-grid">
                        <?php foreach ($items as $item): ?>
                            <article class="item-card">
                                <div class="item-photo">
                                    <img src="<?= e($item['image'] ?: placeholder_image($item['name'], $item['category'])) ?>" alt="<?= e($item['name']) ?>">
                                    <span class="badge <?= item_status_class($item['status']) ?>"><?= e($item['status']) ?></span>
                                </div>
                                <div class="item-body">
                                    <h3><?= e($item['name']) ?></h3>
                                    <p><strong><?= e($item['category']) ?></strong></p>
                                    <p><?= e($item['location']) ?></p>
                                    <div class="card-actions">
                                        <a class="button secondary" href="?page=items&edit=<?= (int) $item['id'] ?>">Editar</a>
                                        <form method="post">
                                            <input type="hidden" name="action" value="delete_item">
                                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                            <button class="danger-button" type="submit">Remover</button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    </div>
<?php endif; ?>
</body>
</html>
