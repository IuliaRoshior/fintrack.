<?php

declare(strict_types=1);

/**
 * Единственная ответственность этого класса: вывод HTML-таблицы.
 */
final class TransactionTableRenderer
{
    /**
     * Превращает список транзакций в HTML-таблицу из семи столбцов.
     * Текстовые значения экранируются для безопасного вывода.
     *
     * @param Transaction[] $transactions Транзакции для отображения.
     * @return string Готовый HTML-код таблицы.
     */
    public function render(array $transactions): string
    {
        $rows = [];

        foreach ($transactions as $transaction) {
            $id = $transaction->getId();
            $date = (new DateTimeImmutable($transaction->getDate()))->format('d.m.Y');
            $amount = number_format($transaction->getAmount(), 2, ',', ' ');
            $description = self::escape($transaction->getDescription());
            $merchant = self::escape($transaction->getMerchant());
            $category = self::escape($transaction->getCategory());
            $days = $transaction->getDaysSinceTransaction();

            $rows[] = <<<HTML
                <tr>
                    <td class="cell-id">#{$id}</td>
                    <td>{$date}</td>
                    <td class="cell-amount">{$amount} <span>MDL</span></td>
                    <td>{$description}</td>
                    <td class="cell-merchant">{$merchant}</td>
                    <td><span class="category-pill">{$category}</span></td>
                    <td class="cell-days">{$days}</td>
                </tr>
                HTML;
        }

        if ($rows === []) {
            $rows[] = '<tr><td class="empty-state" colspan="7">Транзакции не найдены. Попробуйте изменить поиск.</td></tr>';
        }

        $tableBody = implode("\n", $rows);

        return <<<HTML
            <div class="table-scroll">
                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Дата</th>
                            <th>Сумма</th>
                            <th>Описание</th>
                            <th>Получатель</th>
                            <th>Категория</th>
                            <th>Дней назад</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$tableBody}
                    </tbody>
                </table>
            </div>
            HTML;
    }

    /**
     * Защищает HTML-таблицу от внедрения HTML и JavaScript.
     *
     * @param string $value Исходный пользовательский текст.
     * @return string Экранированный текст для HTML.
     */
    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
