<?php

declare(strict_types=1);

date_default_timezone_set('Europe/Chisinau');

require_once __DIR__ . '/../src/Transaction.php';
require_once __DIR__ . '/../src/TransactionStorageInterface.php';
require_once __DIR__ . '/../src/TransactionRepository.php';
require_once __DIR__ . '/../src/TransactionManager.php';
require_once __DIR__ . '/../src/TransactionTableRenderer.php';

/**
 * Прерывает проверку, если условие не выполнено.
 *
 * @param bool $condition Проверяемое условие.
 * @param string $message Описание проверки.
 * @return void
 */
function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('Ошибка проверки: ' . $message);
    }
}

$repository = new TransactionRepository();
$first = new Transaction(1, '2026-10-01', 40.50, 'Книга', 'Librarius', 'Книги');
$second = new Transaction(2, '2026-10-03', 120.00, 'Кофе', 'Cafe', 'Кафе');
$third = new Transaction(3, '2026-10-02', 19.50, 'Кофе с собой', 'Cafe', 'Кафе');
$repository->addTransaction($first);
$repository->addTransaction($second);
$repository->addTransaction($third);

$manager = new TransactionManager($repository);
$renderer = new TransactionTableRenderer();

check(count($repository->getAllTransactions()) === 3, 'добавление');
check($repository->findById(2) === $second, 'поиск по ID');
check($repository->findById(99) === null, 'несуществующий ID');
check(abs($manager->calculateTotalAmount() - 180.0) < .001, 'общая сумма');
check(abs($manager->calculateTotalAmountByDateRange('2026-10-02', '2026-10-03') - 139.5) < .001, 'сумма за период');
check($manager->countTransactionsByMerchant('cafe') === 2, 'подсчёт по получателю');
check($manager->sortTransactionsByDate()[0]->getId() === 2, 'сортировка по дате');
check($manager->sortTransactionsByAmountDesc()[0]->getId() === 2, 'сортировка по сумме');
check(substr_count($renderer->render($repository->getAllTransactions()), '<tr>') === 4, 'HTML-таблица');

$repository->removeTransactionById(2);
check($repository->findById(2) === null, 'удаление');
check($first->getDaysSinceTransaction() >= 0, 'дни с момента операции');

echo "Все проверки пройдены.\n";
