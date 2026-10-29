<?php

declare(strict_types=1);

/**
 * Хранилище транзакций в памяти.
 * Никаких расчётов: только добавление, удаление и поиск.
 */
class TransactionRepository implements TransactionStorageInterface
{
    /** @var array<int, Transaction> Транзакции, индексированные по ID. */
    private array $transactions = [];

    /**
     * Добавляет транзакцию, запрещая повторяющиеся ID.
     *
     * @param Transaction $transaction Объект для сохранения.
     * @return void
     */
    public function addTransaction(Transaction $transaction): void
    {
        $id = $transaction->getId();

        if ($this->findById($id) !== null) {
            throw new InvalidArgumentException("Транзакция #{$id} уже существует.");
        }

        $this->transactions[$id] = $transaction;
    }

    /**
     * Удаляет транзакцию, если такой ID существует.
     *
     * @param int $id Номер удаляемой транзакции.
     * @return void
     */
    public function removeTransactionById(int $id): void
    {
        unset($this->transactions[$id]);
    }

    /**
     * Возвращает копию списка транзакций.
     *
     * @return Transaction[] Все сохранённые транзакции.
     */
    public function getAllTransactions(): array
    {
        return array_values($this->transactions);
    }

    /**
     * Находит транзакцию по номеру.
     *
     * @param int $id Номер искомой транзакции.
     * @return Transaction|null Объект или null, если не найден.
     */
    public function findById(int $id): ?Transaction
    {
        return $this->transactions[$id] ?? null;
    }
}
