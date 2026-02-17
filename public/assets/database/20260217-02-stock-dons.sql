-- Migration: Table stock des restes de dons non distribués
-- Date: 2026-02-17
-- Description: Stocker les restes de dons quand les besoins sont satisfaits

USE bngrc;

CREATE TABLE IF NOT EXISTS bngrc_stock_dons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('nature', 'materiaux', 'argent') NOT NULL,
    designation VARCHAR(100) NOT NULL,
    quantite INT NOT NULL DEFAULT 0,
    datestock DATE NOT NULL,
    UNIQUE KEY unique_type_designation (type, designation)
);

-- Permettre les attributions depuis le stock (sans idDon spécifique)
ALTER TABLE bngrc_attributions MODIFY idDons INT NULL;

UPDATE bngrc_dons SET stock = 0;
