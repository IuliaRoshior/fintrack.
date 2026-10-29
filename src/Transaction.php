<?php

declare(strict_types=1);

/**
 * Одна банковская транзакция.
 * Все её данные закрыты для прямого изменения извне.
 */
class Transaction
{
    /**
     * Создаёт транзакцию с указанными данными.
     *
     * @param int    $id          Уникальный номер транзакции.
     * @param string $date        Дата в формате YYYY-MM-DD.
     * @param float  $amount      Сумма операции в MDL.
     * @param string $description Назначение платежа.
     * @param string $merchant    Название получателя.
     * @param string $category    Категория получателя.
     */
    public function __construct(
        private int $id,
        private string $date,
        private float $amount,
        private string $description,
        private string $merchant,
        private string $category
    ) {
        $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if ($parsedDate === false || $parsedDate->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException('Укажите корректную дату.');
        }

        if ($id <= 0 || !is_finite($amount) || $amount <= 0) {
            throw new InvalidArgumentException('ID и сумма должны быть больше нуля.');
        }

        if (trim($description) === '' || trim($merchant) === '' || trim($category) === '') {
            throw new InvalidArgumentException('Заполните описание, получателя и категорию.');
        }
    }

    /** @return int Уникальный номер транзакции. */
    public function getId(): int
    {
        return $this->id;
    }

    /** @return string Дата транзакции в формате YYYY-MM-DD. */
    public function getDate(): string
    {
        return $this->date;
    }

    /** @return float Сумма транзакции. */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /** @return string Описание транзакции. */
    public function getDescription(): string
    {
        return $this->description;
    }

    /** @return string Название получателя платежа. */
    public function getMerchant(): string
    {
        return $this->merchant;
    }

    /** @return string Категория получателя платежа. */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * Определяет, сколько полных календарных дней прошло после операции.
     * Будущие даты возвращают 0 (отрицательных дней нет).
     *
     * @return int Число дней с момента транзакции.
     */
    public function getDaysSinceTransaction(): int
    {
        $operationDate = new DateTimeImmutable($this->date);
        $today = new DateTimeImmutable('today');

        return max(0, (int) $operationDate->diff($today)->format('%r%a'));
    }
}
