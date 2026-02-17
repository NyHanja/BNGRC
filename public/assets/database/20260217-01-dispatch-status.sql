-- Migration: Ajouter le statut de dispatch aux dons
-- Date: 2026-02-17
-- Description: Colonne 'dispatched' pour savoir si un don a été distribué ou non

USE bngrc;

-- Ajouter la colonne dispatched (0 = disponible, 1 = dispatché)
ALTER TABLE bngrc_dons ADD COLUMN dispatched TINYINT(1) NOT NULL DEFAULT 0;

-- Marquer les dons déjà distribués comme dispatched
UPDATE bngrc_dons d SET d.dispatched = 1 
WHERE d.id IN (SELECT DISTINCT idDons FROM bngrc_attributions);
