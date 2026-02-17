
-- CREATE OR REPLACE VIEW vue_besoins_non_satisfaits AS
-- SELECT
--     v.nom AS nomVille,
--     v.region AS regionVille,
--     b.type AS typeBesoin,
--     b.designation AS designationBesoin,
--     b.quantite AS quantiteBesoin,
--     COALESCE(SUM(a.quantiteAttribuee), 0) AS quantiteAttribuee,
--     (b.quantite - COALESCE(SUM(a.quantiteAttribuee), 0)) AS quantiteNonSatisfaite
-- FROM
--     bngrc_villes v
-- JOIN
--     bngrc_besoins b ON v.id = b.idVille
-- LEFT JOIN
--     bngrc_attributions a ON b.id = a.idVille AND b.designation = a.designation
--     where (b.quantite - COALESCE(SUM(a.quantiteAttribuee), 0)) > 0
-- GROUP BY
--     v.id, b.id;
-- SELECT * FROM vue_besoins_non_satisfaits;

-- CREATE OR REPLACE VIEW vue_besoins_non_satisfaits AS
-- SELECT
--     v.nom AS nomVille,
--     v.region AS regionVille,
--     b.id AS idBesoin,
--     b.type AS typeBesoin,
--     b.designation AS designationBesoin,
--     b.quantite AS quantiteBesoin,
--     COALESCE(SUM(a.quantiteAttribuee), 0) AS quantiteAttribuee,
--     (b.quantite - COALESCE(SUM(a.quantiteAttribuee), 0)) AS quantiteNonSatisfaite
-- FROM
--     bngrc_villes v
-- JOIN
--     bngrc_besoins b ON v.id = b.idVille
-- LEFT JOIN
--     bngrc_attributions a 
--         ON b.id = a.idBesoin   -- ⚠ correction importante ici
-- GROUP BY
--     v.id, b.id
-- HAVING
--     quantiteNonSatisfaite > 0;

-- SELECT * FROM vue_besoins_non_satisfaits;


-- CREATE OR REPLACE VIEW vue_besoins_non_satisfaits AS
-- SELECT
--     v.nom AS nomVille,
--     v.region AS regionVille,
--     b.id AS idBesoin,
--     b.type AS typeBesoin,
--     b.designation AS designationBesoin,
--     b.quantite AS quantiteBesoin,
--     COALESCE(SUM(a.quantiteAttribuee), 0) AS quantiteAttribuee,
--     (b.quantite - COALESCE(SUM(a.quantiteAttribuee), 0)) AS quantiteNonSatisfaite
-- FROM
--     bngrc_villes v
-- JOIN
--     bngrc_besoins b ON v.id = b.idVille
-- LEFT JOIN
--     bngrc_attributions a 
--         ON b.idVille = a.idVille
--         AND b.designation = a.designation
-- GROUP BY
--     v.id, b.id
-- HAVING
--     quantiteNonSatisfaite > 0;
-- SELECT * FROM vue_besoins_non_satisfaits;









CREATE OR REPLACE VIEW vue_besoins_non_satisfaits AS
SELECT * FROM (
    SELECT
        v.id AS idVille,
        v.nom AS nomVille,
        v.region AS regionVille,
        b.id AS idBesoin,
        b.type AS typeBesoin,
        b.designation AS designationBesoin,
        b.quantite AS quantiteBesoin,

        -- Quantité déjà attribuée (pour nature/materiaux via attributions)
        COALESCE(SUM(a.quantiteAttribuee), 0) AS quantiteAttribuee,

        -- Quantité restante (argent: basé sur repartitionArgent, autres: basé sur attributions)
        CASE 
            WHEN b.type = 'argent' THEN 
                GREATEST(b.quantite - COALESCE(ra_sub.totalReparti / NULLIF(b.prixUnitaire, 0), 0), 0)
            ELSE 
                (b.quantite - COALESCE(SUM(a.quantiteAttribuee), 0))
        END AS quantiteNonSatisfaite,

        -- Montant total du besoin
        (b.prixUnitaire * b.quantite) AS MontantBesoin,

        -- Montant déjà attribué/réparti
        CASE 
            WHEN b.type = 'argent' THEN COALESCE(ra_sub.totalReparti, 0)
            ELSE (b.prixUnitaire * COALESCE(SUM(a.quantiteAttribuee), 0))
        END AS MontantBesoinAttribue,

        -- Montant restant
        CASE 
            WHEN b.type = 'argent' THEN 
                GREATEST((b.prixUnitaire * b.quantite) - COALESCE(ra_sub.totalReparti, 0), 0)
            ELSE 
                (b.prixUnitaire * (b.quantite - COALESCE(SUM(a.quantiteAttribuee), 0)))
        END AS MontantBesoinNonSatisfait

    FROM bngrc_villes v

    JOIN bngrc_besoins b 
        ON v.id = b.idVille

    LEFT JOIN bngrc_attributions a 
        ON b.idVille = a.idVille
        AND b.designation = a.designation

    -- Sous-requête pour les répartitions argent par besoin
    LEFT JOIN (
        SELECT idBesoin, SUM(montantReparti) AS totalReparti
        FROM repartitionArgent
        GROUP BY idBesoin
    ) ra_sub ON b.id = ra_sub.idBesoin AND b.type = 'argent'

    GROUP BY v.id, b.id
) AS sub
WHERE quantiteNonSatisfaite > 0;

SELECT * FROM vue_besoins_non_satisfaits;
