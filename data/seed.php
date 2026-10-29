<?php

declare(strict_types=1);

/**
 * Начальные данные: 12 операций, из них 10 разных получателей.
 * Даты вычисляются относительно сегодняшнего дня.
 *
 * @return Transaction[]
 */
return [
    new Transaction(1, date('Y-m-d', strtotime('-1 day')), 124.80, 'Покупка продуктов', 'Linella', 'Продукты'),
    new Transaction(2, date('Y-m-d', strtotime('-2 days')), 429.50, 'Закупка на неделю', 'Metro', 'Продукты'),
    new Transaction(3, date('Y-m-d', strtotime('-4 days')), 180.00, 'Пополнение мобильной связи', 'Orange', 'Связь'),
    new Transaction(4, date('Y-m-d', strtotime('-6 days')), 245.00, 'Домашний интернет', 'Moldtelecom', 'Связь'),
    new Transaction(5, date('Y-m-d', strtotime('-8 days')), 98.00, 'Аптечные покупки', 'Farmacia Familiei', 'Здоровье'),
    new Transaction(6, date('Y-m-d', strtotime('-11 days')), 75.00, 'Кофе и десерт', 'Starbucks', 'Кафе'),
    new Transaction(7, date('Y-m-d', strtotime('-14 days')), 63.50, 'Поездка по городу', 'Bolt', 'Транспорт'),
    new Transaction(8, date('Y-m-d', strtotime('-17 days')), 899.00, 'Компьютерные аксессуары', 'Enter', 'Техника'),
    new Transaction(9, date('Y-m-d', strtotime('-22 days')), 179.00, 'Месячная подписка', 'Netflix', 'Подписки'),
    new Transaction(10, date('Y-m-d', strtotime('-26 days')), 135.00, 'Книга по программированию', 'Librarius', 'Книги'),
    new Transaction(11, date('Y-m-d', strtotime('-31 days')), 207.40, 'Продукты для дома', 'Linella', 'Продукты'),
    new Transaction(12, date('Y-m-d', strtotime('-38 days')), 102.00, 'Поездка на такси', 'Bolt', 'Транспорт'),
];
