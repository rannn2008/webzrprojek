-- Migration: Add alamat column to orders table
-- Run this SQL in phpMyAdmin or MySQL client

ALTER TABLE `orders` 
ADD COLUMN `alamat` TEXT NULL AFTER `whatsapp`;

-- Verify the change
DESCRIBE `orders`;
