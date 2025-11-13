-- Міграція для додавання WHMCS полів до таблиць замовлень
-- Виконайте цей файл в phpMyAdmin після імпорту schema.sql

-- Додаємо поля WHMCS до orders_confectionery
ALTER TABLE `orders_confectionery`
ADD COLUMN `whmcs_client_id` int(11) DEFAULT NULL COMMENT 'ID клієнта в WHMCS',
ADD COLUMN `whmcs_order_id` int(11) DEFAULT NULL COMMENT 'ID замовлення в WHMCS',
ADD COLUMN `whmcs_invoice_id` int(11) DEFAULT NULL COMMENT 'ID рахунку в WHMCS',
ADD COLUMN `payment_status` varchar(50) DEFAULT 'unpaid' COMMENT 'Статус оплати з WHMCS',
ADD COLUMN `whmcs_data` text DEFAULT NULL COMMENT 'JSON дані від WHMCS',
ADD INDEX `idx_whmcs_invoice` (`whmcs_invoice_id`),
ADD INDEX `idx_payment_status` (`payment_status`);

-- Додаємо поля WHMCS до orders_fireshow
ALTER TABLE `orders_fireshow`
ADD COLUMN `whmcs_client_id` int(11) DEFAULT NULL COMMENT 'ID клієнта в WHMCS',
ADD COLUMN `whmcs_order_id` int(11) DEFAULT NULL COMMENT 'ID замовлення в WHMCS',
ADD COLUMN `whmcs_invoice_id` int(11) DEFAULT NULL COMMENT 'ID рахунку в WHMCS',
ADD COLUMN `payment_status` varchar(50) DEFAULT 'unpaid' COMMENT 'Статус оплати з WHMCS',
ADD COLUMN `whmcs_data` text DEFAULT NULL COMMENT 'JSON дані від WHMCS',
ADD INDEX `idx_whmcs_invoice` (`whmcs_invoice_id`),
ADD INDEX `idx_payment_status` (`payment_status`);

-- Коментар: Після виконання цієї міграції
-- таблиці будуть готові для інтеграції з WHMCS
