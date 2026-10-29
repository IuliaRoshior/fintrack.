<?php

declare(strict_types=1);

/**
 * Бизнес-логика: вычисления, статистика и сортировка.
 * Данные берёт из интерфейса, сам их не хранит.
 */
class TransactionManager
{
    /**
     * Получает зависимость от хранилища через конструктор.
     *
     * @param TransactionStorageInterface $repository Источник данных.
     */
    public function __construct(
        private TransactionStorageInterface $repository
    ) {
    }

    /**
     * Суммирует все операции.
     *
     * @return float Общая сумма в MDL.
     */
    public function calculateTotalAmount(): float
    {
        $total = 0.0;

        foreach ($this->repository->getAllTransactions() as $transaction) {
            $total += $transaction->getAmount();
        }

        return $total;
    }

    /**
     * Суммирует операции между двумя датами включительно.
     *
     * @param string $startDate Начало периода (YYYY-MM-DD).
     * @param string $endDate   Конец периода (YYYY-MM-DD).
     * @return float Сумма транзакций за выбранный период.
     */
    public function calculateTotalAmountByDateRange(string $startDate, string $endDate): float
    {
        self::validateDate($startDate);
        self::validateDate($endDate);

        if ($startDate > $endDate) {
            throw new InvalidArgumentException('Начало периода должно быть раньше конца.');
        }

        $total = 0.0;

        foreach ($this->repository->getAllTransactions() as $transaction) {
            $date = $transaction->getDate();

            if ($date >= $startDate && $date <= $endDate) {
                $total += $transaction->getAmount();
            }
        }

        return $total;
    }

    /**
     * Подсчитывает операции по названию получателя без учёта регистра.
     *
     * @param string $merchant Название получателя.
     * @return int Количество подходящих транзакций.
     */
    public function countTransactionsByMerchant(string $merchant): int
    {
        $count = 0;

        foreach ($this->repository->getAllTransactions() as $transaction) {
            if (strcasecmp($transaction->getMerchant(), trim($merchant)) === 0) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Сортирует копию списка от новых транзакций к старым.
     *
     * @return Transaction[] Транзакции по убыванию даты.
     */
    public function sortTransactionsByDate(): array
    {
        $transactions = $this->repository->getAllTransactions();

        usort(
            $transactions,
            static fn (Transaction $a, Transaction $b): int =>
                strcmp($b->getDate(), $a->getDate())
        );

        return $transactions;
    }

    /**
     * Сортирует копию списка по сумме, от большей к меньшей.
     *
     * @return Transaction[] Транзакции по убыванию суммы.
     */
    public function sortTransactionsByAmountDesc(): array
    {
        $transactions = $this->repository->getAllTransactions();

        usort(
            $transactions,
            static fn (Transaction $a, Transaction $b): int =>
                $b->getAmount() <=> $a->getAmount()
        );

        return $transactions;
    }

    /**
     * Проверяет корректность строки с датой.
     *
     * @param string $date Дата в формате YYYY-MM-DD.
     * @return void
     */
    private static function validateDate(string $date): void
    {
        $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if ($parsedDate === false || $parsedDate->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException('Дата должна быть в формате YYYY-MM-DD.');
        }
    }
}
