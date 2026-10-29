<?php

declare(strict_types=1);

/**
 * Контракт для любого хранилища банковских транзакций.
 */
interface TransactionStorageInterface
{
    /**
     * Сохраняет новую транзакцию.
     *
     * @param Transaction $transaction Добавляемая транзакция.
     * @return void
     */
    public function addTransaction(Transaction $transaction): void;

    /**
     * Удаляет транзакцию по номеру.
     *
     * @param int $id ID удаляемой транзакции.
     * @return void
     */
    public function removeTransactionById(int $id): void;

    /**
     * Возвращает все сохранённые транзакции.
     *
     * @return Transaction[] Массив объектов Transaction.
     */
    public function getAllTransactions(): array;

    /**
     * Ищет одну транзакцию по ID.
     *
     * @param int $id Номер для поиска.
     * @return Transaction|null Найденная транзакция или null.
     */
    public function findById(int $id): ?Transaction;
}
