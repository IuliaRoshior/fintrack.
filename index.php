<?php

declare(strict_types=1);

// 1. Подключаем классы и задаём часовой пояс.
date_default_timezone_set('Europe/Chisinau');

require_once __DIR__ . '/src/Transaction.php';
require_once __DIR__ . '/src/TransactionStorageInterface.php';
require_once __DIR__ . '/src/TransactionRepository.php';
require_once __DIR__ . '/src/TransactionManager.php';
require_once __DIR__ . '/src/TransactionTableRenderer.php';

/**
 * Безопасно выводит текст в HTML.
 *
 * @param string $value Строка для вывода.
 * @return string Строка с экранированными спецсимволами.
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// 2. В сессии храним список между обновлениями страницы.
//    Классы подключены ДО session_start(), чтобы PHP восстановил объекты.
session_start();

if (!isset($_SESSION['transactions'])) {
    $_SESSION['transactions'] = require __DIR__ . '/data/seed.php';
    $_SESSION['next_id'] = 13;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

// 3. Репозиторий хранит объекты, менеджер считает, renderer рисует таблицу.
$repository = new TransactionRepository();

foreach ($_SESSION['transactions'] as $transaction) {
    $repository->addTransaction($transaction);
}

$manager = new TransactionManager($repository);
$renderer = new TransactionTableRenderer();

// 4. Обработка кнопок «Добавить» и «Удалить».
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $postedToken = (string) ($_POST['csrf_token'] ?? '');

        if (!hash_equals($_SESSION['csrf_token'], $postedToken)) {
            throw new RuntimeException('Неверный токен формы. Обновите страницу.');
        }

        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'add') {
            $amount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_FLOAT);

            if ($amount === false || $amount <= 0) {
                throw new InvalidArgumentException('Введите положительную сумму.');
            }

            // Транзакцию создаёт основной файл, а не TransactionManager.
            $transaction = new Transaction(
                (int) $_SESSION['next_id'],
                (string) ($_POST['date'] ?? ''),
                (float) $amount,
                trim((string) ($_POST['description'] ?? '')),
                trim((string) ($_POST['merchant'] ?? '')),
                trim((string) ($_POST['category'] ?? ''))
            );

            $repository->addTransaction($transaction);
            $_SESSION['next_id']++;
            $_SESSION['message'] = 'Транзакция успешно добавлена.';
        } elseif ($action === 'remove') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

            if ($id === false || $id <= 0) {
                throw new InvalidArgumentException('Укажите корректный ID.');
            }

            if ($repository->findById($id) === null) {
                throw new InvalidArgumentException("Транзакция #{$id} не найдена.");
            }

            $repository->removeTransactionById($id);
            $_SESSION['message'] = "Транзакция #{$id} удалена.";
        } else {
            throw new InvalidArgumentException('Неизвестное действие.');
        }

        $_SESSION['transactions'] = $repository->getAllTransactions();
    } catch (InvalidArgumentException | RuntimeException $exception) {
        $_SESSION['error'] = $exception->getMessage();
    }

    // Перенаправление предотвращает повторную отправку формы при F5.
    header('Location: index.php', true, 303);
    exit;
}

// 5. Параметры поиска, сортировки и статистики.
$sort = (string) ($_GET['sort'] ?? 'date');
$searchQuery = trim((string) ($_GET['q'] ?? ''));
$startDate = (string) ($_GET['start_date'] ?? date('Y-m-01'));
$endDate = (string) ($_GET['end_date'] ?? date('Y-m-d'));
$selectedMerchant = trim((string) ($_GET['merchant'] ?? ''));

$allTransactions = $repository->getAllTransactions();
$merchants = array_unique(array_map(
    static fn (Transaction $transaction): string => $transaction->getMerchant(),
    $allTransactions
));
sort($merchants, SORT_STRING);

$visibleTransactions = match ($sort) {
    'amount' => $manager->sortTransactionsByAmountDesc(),
    'date' => $manager->sortTransactionsByDate(),
    default => $allTransactions,
};

// 6. Поиск по названию получателя и описанию + фильтр по получателю.
//    Сортировка сохраняется, поскольку перебираем уже отсортированный список.
if ($searchQuery !== '' || $selectedMerchant !== '') {
    $filteredTransactions = [];

    // preg_quote() делает пользовательский текст обычной строкой, а не регулярным выражением.
    // Флаги i и u позволяют искать без учёта регистра, в том числе по русскому тексту.
    $searchPattern = $searchQuery !== ''
        ? '~' . preg_quote($searchQuery, '~') . '~iu'
        : null;

    foreach ($visibleTransactions as $transaction) {
        $matchesSearch = $searchPattern === null
            || preg_match($searchPattern, $transaction->getMerchant()) === 1
            || preg_match($searchPattern, $transaction->getDescription()) === 1;
        $matchesMerchant = $selectedMerchant === ''
            || $transaction->getMerchant() === $selectedMerchant;

        if ($matchesSearch && $matchesMerchant) {
            $filteredTransactions[] = $transaction;
        }
    }

    $visibleTransactions = $filteredTransactions;
}

$periodError = null;
$periodTotal = null;

try {
    $periodTotal = $manager->calculateTotalAmountByDateRange($startDate, $endDate);
} catch (InvalidArgumentException $exception) {
    $periodError = $exception->getMessage();
}

$total = $manager->calculateTotalAmount();
$merchantCount = $selectedMerchant !== ''
    ? $manager->countTransactionsByMerchant($selectedMerchant)
    : count($merchants);
$message = $_SESSION['message'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['message'], $_SESSION['error']);

header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0c1424">
    <title>FinTrack — Управление транзакциями</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="topbar">
        <div class="container topbar-inner">
            <div class="brand">
                <span class="brand-mark">✳</span>
                <span>fintrack<span class="brand-dot">.</span></span>
            </div>
            <div class="topbar-note">Лабораторная №5 <span>·</span> ООП в PHP</div>
        </div>
    </header>

    <main class="container main-content">
        <div class="page-heading">
            <div>
                <div class="eyebrow"><span class="status-dot"></span> МОЙ ФИНАНСОВЫЙ ОБЗОР</div>
                <h1>Управление <span>транзакциями</span></h1>
                <p>Добавляйте, находите и сортируйте операции. Все расчёты выполняются на PHP.</p>
            </div>
            <div class="page-tag">PHP 8+ <span> / </span> OOP</div>
        </div>

        <?php if ($message !== null): ?>
            <div class="notice notice-success" role="status"><?= e($message) ?></div>
        <?php endif; ?>
        <?php if ($error !== null): ?>
            <div class="notice notice-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <section class="stats-grid" aria-label="Статистика транзакций">
            <article class="stat-card stat-primary">
                <div class="stat-label">Общая сумма <span class="stat-icon">↗</span></div>
                <div class="stat-value"><?= number_format($total, 2, ',', ' ') ?> <small>MDL</small></div>
                <div class="stat-note">За всё время</div>
            </article>
            <article class="stat-card">
                <div class="stat-label">За выбранный период <span class="stat-icon">◷</span></div>
                <div class="stat-value">
                    <?= $periodTotal !== null ? number_format($periodTotal, 2, ',', ' ') : '—' ?>
                    <?php if ($periodTotal !== null): ?><small>MDL</small><?php endif; ?>
                </div>
                <div class="stat-note"><?= $periodError !== null ? e($periodError) : 'Обе даты включены' ?></div>
            </article>
            <article class="stat-card">
                <div class="stat-label">Всего операций <span class="stat-icon">▤</span></div>
                <div class="stat-value"><?= count($allTransactions) ?></div>
                <div class="stat-note">В текущей коллекции</div>
            </article>
            <article class="stat-card">
                <div class="stat-label">
                    <?= $selectedMerchant !== '' ? 'Операций у получателя' : 'Уникальных получателей' ?>
                    <span class="stat-icon">◎</span>
                </div>
                <div class="stat-value"><?= $merchantCount ?></div>
                <div class="stat-note" title="<?= e($selectedMerchant !== '' ? $selectedMerchant : 'Все получатели') ?>">
                    <?= e($selectedMerchant !== '' ? $selectedMerchant : 'Все получатели') ?>
                </div>
            </article>
        </section>

        <section class="panel filters-panel" aria-labelledby="filters-title">
            <div class="section-heading">
                <div>
                    <h2 id="filters-title">Поиск и фильтры</h2>
                    <p>Поиск по получателю и описанию, сортировка и расчёт суммы за период.</p>
                </div>
                <a class="text-link" href="index.php">Сбросить ↗</a>
            </div>

            <form method="get" class="filter-grid">
                <label class="field">
                    <span>Поиск по операциям</span>
                    <input type="search" name="q" placeholder="Orange, кофе, книга..." value="<?= e($searchQuery) ?>">
                </label>
                <label class="field">
                    <span>Сортировать</span>
                    <select name="sort">
                        <option value="date" <?= $sort === 'date' ? 'selected' : '' ?>>По дате: новые сначала</option>
                        <option value="amount" <?= $sort === 'amount' ? 'selected' : '' ?>>По сумме: по убыванию</option>
                        <option value="all" <?= $sort === 'all' ? 'selected' : '' ?>>Без сортировки</option>
                    </select>
                </label>
                <label class="field">
                    <span>Дата от</span>
                    <input type="date" name="start_date" value="<?= e($startDate) ?>">
                </label>
                <label class="field">
                    <span>Дата до</span>
                    <input type="date" name="end_date" value="<?= e($endDate) ?>">
                </label>
                <label class="field">
                    <span>Получатель</span>
                    <select name="merchant">
                        <option value="" <?= $selectedMerchant === '' ? 'selected' : '' ?>>Все получатели</option>
                        <?php foreach ($merchants as $merchant): ?>
                            <option value="<?= e($merchant) ?>" <?= $merchant === $selectedMerchant ? 'selected' : '' ?>>
                                <?= e($merchant) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit" class="button button-primary filter-button">Применить</button>
            </form>
            <?php if ($periodError !== null): ?>
                <p class="inline-error" role="alert"><?= e($periodError) ?></p>
            <?php endif; ?>
        </section>

        <div class="content-grid">
            <section class="panel table-panel" aria-labelledby="table-title">
                <div class="section-heading table-heading">
                    <div>
                        <h2 id="table-title">История операций</h2>
                        <p>Найдено записей: <?= count($visibleTransactions) ?></p>
                    </div>
                    <span class="live-label"><span class="status-dot"></span> Активная коллекция</span>
                </div>
                <?php echo $renderer->render($visibleTransactions); ?>
            </section>

            <aside class="side-column">
                <section class="panel form-panel" aria-labelledby="add-title">
                    <div class="section-heading">
                        <div>
                            <h2 id="add-title">Новая операция</h2>
                            <p>Добавить транзакцию в список</p>
                        </div>
                    </div>
                    <form method="post" class="vertical-form">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="add">
                        <label class="field">
                            <span>Дата операции</span>
                            <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>
                        </label>
                        <label class="field">
                            <span>Сумма, MDL</span>
                            <input type="number" name="amount" min="0.01" step="0.01" placeholder="0.00" required>
                        </label>
                        <label class="field">
                            <span>Описание</span>
                            <input type="text" name="description" maxlength="100" placeholder="Например, Покупка книги" required>
                        </label>
                        <label class="field">
                            <span>Получатель</span>
                            <input type="text" name="merchant" maxlength="80" placeholder="Например, Librarius" required>
                        </label>
                        <label class="field">
                            <span>Категория</span>
                            <select name="category" required>
                                <option value="Продукты">Продукты</option>
                                <option value="Связь">Связь</option>
                                <option value="Здоровье">Здоровье</option>
                                <option value="Кафе">Кафе</option>
                                <option value="Транспорт">Транспорт</option>
                                <option value="Техника">Техника</option>
                                <option value="Подписки">Подписки</option>
                                <option value="Книги">Книги</option>
                                <option value="Другое">Другое</option>
                            </select>
                        </label>
                        <button type="submit" class="button button-primary full-width">＋ Добавить транзакцию</button>
                    </form>
                </section>

                <section class="panel remove-panel" aria-labelledby="remove-title">
                    <div class="section-heading">
                        <div>
                            <h2 id="remove-title">Удаление записи</h2>
                            <p>ID указан в первом столбце таблицы.</p>
                        </div>
                    </div>
                    <form method="post" class="remove-form">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="remove">
                        <label class="field">
                            <span>ID для удаления</span>
                            <input type="number" name="id" min="1" placeholder="Например, 5" required>
                        </label>
                        <button type="submit" class="button button-danger">Удалить</button>
                    </form>
                </section>
            </aside>
        </div>

        <footer class="footer">
            <span>FinTrack · Учебный проект</span>
            <span>Transaction · Repository · Manager · Renderer · Interface</span>
        </footer>
    </main>
</body>
</html>
