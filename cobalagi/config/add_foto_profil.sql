-- Script to add foto_profil column to customers table
ALTER TABLE customers ADD COLUMN foto_profil VARCHAR(255) DEFAULT NULL;
