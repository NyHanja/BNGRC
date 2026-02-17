-- --------------------------------------------------
-- Données : Villes, Besoins et Dons
-- Date : 2026-02-17
-- Source : Tableau de données BNGRC
-- --------------------------------------------------

USE bngrc;

-- Vider les données existantes (ordre inverse des FK)
DELETE FROM bngrc_attributions;
DELETE FROM bngrc_besoins;
DELETE FROM bngrc_dons;
DELETE FROM bngrc_villes;

-- Réinitialiser les auto-incréments
ALTER TABLE bngrc_villes AUTO_INCREMENT = 1;
ALTER TABLE bngrc_besoins AUTO_INCREMENT = 1;
ALTER TABLE bngrc_attributions AUTO_INCREMENT = 1;
ALTER TABLE bngrc_dons AUTO_INCREMENT = 1;

-- =============================================
-- VILLES (5 villes)
-- =============================================
INSERT INTO bngrc_villes (nom, region) VALUES
('Toamasina', 'Atsinanana'),
('Mananjary', 'Vatovavy-Fitovinany'),
('Farafangana', 'Atsimo-Atsinanana'),
('Nosy Be', 'Diana'),
('Morondava', 'Menabe');

-- IDs attendus :
-- 1 = Toamasina
-- 2 = Mananjary
-- 3 = Farafangana
-- 4 = Nosy Be
-- 5 = Morondava

-- =============================================
-- BESOINS (26 besoins, insérés par ordre croissant)
-- =============================================
INSERT INTO bngrc_besoins (idVille, type, designation, prixUnitaire, quantite, dateSaisie) VALUES
-- Ordre 1 : Toamasina - Bâche
(1, 'materiaux', 'Bâche', 15000, 200, '2026-02-15'),
-- Ordre 2 : Nosy Be - Tôle
(4, 'materiaux', 'Tôle', 25000, 40, '2026-02-15'),
-- Ordre 3 : Mananjary - Argent
(2, 'argent', 'Argent', 6000000, 1, '2026-02-15'),
-- Ordre 4 : Toamasina - Eau (L)
(1, 'nature', 'Eau (L)', 1000, 1500, '2026-02-15'),
-- Ordre 5 : Nosy Be - Riz (kg)
(4, 'nature', 'Riz (kg)', 3000, 300, '2026-02-15'),
-- Ordre 6 : Mananjary - Tôle
(2, 'materiaux', 'Tôle', 25000, 80, '2026-02-15'),
-- Ordre 7 : Nosy Be - Argent
(4, 'argent', 'Argent', 4000000, 1, '2026-02-15'),
-- Ordre 8 : Farafangana - Bâche
(3, 'materiaux', 'Bâche', 15000, 150, '2026-02-16'),
-- Ordre 9 : Mananjary - Riz (kg)
(2, 'nature', 'Riz (kg)', 3000, 500, '2026-02-15'),
-- Ordre 10 : Farafangana - Argent
(3, 'argent', 'Argent', 8000000, 1, '2026-02-16'),
-- Ordre 11 : Morondava - Riz (kg)
(5, 'nature', 'Riz (kg)', 3000, 700, '2026-02-16'),
-- Ordre 12 : Toamasina - Argent
(1, 'argent', 'Argent', 12000000, 1, '2026-02-16'),
-- Ordre 13 : Morondava - Argent
(5, 'argent', 'Argent', 10000000, 1, '2026-02-16'),
-- Ordre 14 : Farafangana - Eau (L)
(3, 'nature', 'Eau (L)', 1000, 1000, '2026-02-15'),
-- Ordre 15 : Morondava - Bâche
(5, 'materiaux', 'Bâche', 15000, 180, '2026-02-16'),
-- Ordre 16 : Toamasina - Groupe électrogène
(1, 'materiaux', 'Groupe électrogène', 6750000, 3, '2026-02-15'),
-- Ordre 17 : Toamasina - Riz (kg)
(1, 'nature', 'Riz (kg)', 3000, 800, '2026-02-16'),
-- Ordre 18 : Nosy Be - Haricots
(4, 'nature', 'Haricots', 4000, 200, '2026-02-16'),
-- Ordre 19 : Mananjary - Clous (kg)
(2, 'materiaux', 'Clous (kg)', 8000, 60, '2026-02-16'),
-- Ordre 20 : Morondava - Eau (L)
(5, 'nature', 'Eau (L)', 1000, 1200, '2026-02-15'),
-- Ordre 21 : Farafangana - Riz (kg)
(3, 'nature', 'Riz (kg)', 3000, 600, '2026-02-15'),
-- Ordre 22 : Morondava - Bois
(5, 'materiaux', 'Bois', 10000, 150, '2026-02-15'),
-- Ordre 23 : Toamasina - Tôle
(1, 'materiaux', 'Tôle', 25000, 120, '2026-02-16'),
-- Ordre 24 : Nosy Be - Clous (kg)
(4, 'materiaux', 'Clous (kg)', 8000, 30, '2026-02-16'),
-- Ordre 25 : Mananjary - Huile (L)
(2, 'nature', 'Huile (L)', 6000, 120, '2026-02-15'),
-- Ordre 26 : Farafangana - Bois
(3, 'materiaux', 'Bois', 10000, 100, '2026-02-15');

-- =============================================
-- DONS (16 dons)
-- donateur = 'Donateur anonyme' (pas de colonne donateur dans l'image)
-- Pour argent : montantUnitaire = montant, quantite = 1
-- Pour nature/materiaux : montantUnitaire = 1, quantite = valeur
-- =============================================
INSERT INTO bngrc_dons (donateur, type, designation, montantUnitaire, quantite, dateSaisie) VALUES
-- Don 1 : argent 5 000 000
('Donateur 1', 'argent', 'Argent', 5000000, 1, '2026-02-16'),
-- Don 2 : argent 3 000 000
('Donateur 2', 'argent', 'Argent', 3000000, 1, '2026-02-16'),
-- Don 3 : argent 4 000 000
('Donateur 3', 'argent', 'Argent', 4000000, 1, '2026-02-17'),
-- Don 4 : argent 1 500 000
('Donateur 4', 'argent', 'Argent', 1500000, 1, '2026-02-17'),
-- Don 5 : argent 6 000 000
('Donateur 5', 'argent', 'Argent', 6000000, 1, '2026-02-17'),
-- Don 6 : Riz (kg) 400
('Donateur 6', 'nature', 'Riz (kg)', 1, 400, '2026-02-16'),
-- Don 7 : Eau (L) 600
('Donateur 7', 'nature', 'Eau (L)', 1, 600, '2026-02-16'),
-- Don 8 : Tôle 50
('Donateur 8', 'materiaux', 'Tôle', 1, 50, '2026-02-17'),
-- Don 9 : Bâche 70
('Donateur 9', 'materiaux', 'Bâche', 1, 70, '2026-02-17'),
-- Don 10 : Haricots 100
('Donateur 10', 'nature', 'Haricots', 1, 100, '2026-02-17'),
-- Don 11 : Riz (kg) 2000
('Donateur 11', 'nature', 'Riz (kg)', 1, 2000, '2026-02-18'),
-- Don 12 : Tôle 300
('Donateur 12', 'materiaux', 'Tôle', 1, 300, '2026-02-18'),
-- Don 13 : Eau (L) 5000
('Donateur 13', 'nature', 'Eau (L)', 1, 5000, '2026-02-18'),
-- Don 14 : argent 20 000 000
('Donateur 14', 'argent', 'Argent', 20000000, 1, '2026-02-19'),
-- Don 15 : Bâche 500
('Donateur 15', 'materiaux', 'Bâche', 1, 500, '2026-02-19'),
-- Don 16 : Haricots 88
('Donateur 16', 'nature', 'Haricots', 1, 88, '2026-02-17');
