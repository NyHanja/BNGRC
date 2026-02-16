-- --------------------------------------------------
-- Script MySQL : Base BNGRC – Gestion des Dons
-- Créé pour Harena, Nekena et NyHanja
-- --------------------------------------------------

-- 1️⃣ Création de la base de données
CREATE DATABASE IF NOT EXISTS bngrc;
USE bngrc;

-- 2️⃣ Table villes
CREATE TABLE IF NOT EXISTS bngrc_villes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    region VARCHAR(100) NOT NULL
);

-- Données exemples pour villes
INSERT INTO villes (nom, region) VALUES
('Antananarivo', 'Analamanga'),
('Toamasina', 'Atsinanana'),
('Fianarantsoa', 'Haute Matsiatra');

-- 3️⃣ Table besoins
CREATE TABLE IF NOT EXISTS bngrc_besoins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idVille INT NOT NULL,
    type ENUM('nature', 'materiaux', 'argent') NOT NULL,
    designation VARCHAR(100) NOT NULL,
    prixUnitaire DECIMAL(10,2) NOT NULL,
    quantite INT NOT NULL,
    dateSaisie DATE NOT NULL,
    FOREIGN KEY (idVille) REFERENCES villes(id) ON DELETE CASCADE
);

-- Données exemples pour besoins
INSERT INTO besoins (idVille, type, designation, prixUnitaire, quantite, dateSaisie) VALUES
(1, 'nature', 'riz', 2.50, 1000, '2026-02-15'),
(1, 'materiaux', 'tôle', 10.00, 200, '2026-02-15'),
(2, 'argent', 'fonds secours', 1.00, 5000, '2026-02-14'),
(3, 'nature', 'huile', 3.00, 800, '2026-02-13');

-- 4️⃣ Table dons
CREATE TABLE IF NOT EXISTS bngrc_dons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    donateur VARCHAR(100) NOT NULL,
    type ENUM('nature', 'materiaux', 'argent') NOT NULL,
    designation VARCHAR(100) NOT NULL,
    montantUnitaire DECIMAL(10,2) NOT NULL DEFAULT 1,
    quantite INT NOT NULL,
    dateSaisie DATE NOT NULL
);

-- Données exemples pour dons
INSERT INTO dons (donateur, type, designation, montantUnitaire, quantite, dateSaisie) VALUES
('BNGRC', 'nature', 'riz', 2.50, 500, '2026-02-15'),
('Association A', 'materiaux', 'tôle', 10.00, 100, '2026-02-14'),
('Donateur Privé', 'argent', 'fonds secours', 1.00, 2000, '2026-02-13');

-- 5️⃣ Table attributions
CREATE TABLE IF NOT EXISTS bngrc_attributions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idDons INT NOT NULL,
    idVille INT NOT NULL,
    designation VARCHAR(100) NOT NULL,
    quantiteAttribuee INT NOT NULL,
    dateAttribution DATE NOT NULL,
    FOREIGN KEY (idDons) REFERENCES dons(id) ON DELETE CASCADE,
    FOREIGN KEY (idVille) REFERENCES villes(id) ON DELETE CASCADE
);

-- Données exemples pour attributions
INSERT INTO attributions (idDons, idVille, designation, quantiteAttribuee, dateAttribution) VALUES
(1, 1, 'riz', 300, '2026-02-16'),
(2, 1, 'tôle', 50, '2026-02-16'),
(3, 2, 'fonds secours', 1000, '2026-02-16');
