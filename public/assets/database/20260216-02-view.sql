
CREATE OR REPLACE VIEW vue_besoins_non_satisfaits AS
SELECT
    v.nom AS nomVille,
    v.region AS regionVille,
    b.type AS typeBesoin,
    b.designation AS designationBesoin,
    b.quantite AS quantiteBesoin,
    COALESCE(SUM(a.quantiteAttribuee), 0) AS quantiteAttribuee,
    (b.quantite - COALESCE(SUM(a.quantiteAttribuee), 0)) AS quantiteNonSatisfaite
FROM
    bngrc_villes v
JOIN
    bngrc_besoins b ON v.id = b.idVille
LEFT JOIN
    bngrc_attributions a ON b.id = a.idVille AND b.designation = a.designation
    where (b.quantite - COALESCE(SUM(a.quantiteAttribuee), 0)) > 0
GROUP BY
    v.id, b.id;
SELECT * FROM vue_besoins_non_satisfaits;

CREATE OR REPLACE VIEW vue_besoins_non_satisfaits AS
SELECT
    v.nom AS nomVille,
    v.region AS regionVille,
    b.id AS idBesoin,
    b.type AS typeBesoin,
    b.designation AS designationBesoin,
    b.quantite AS quantiteBesoin,
    COALESCE(SUM(a.quantiteAttribuee), 0) AS quantiteAttribuee,
    (b.quantite - COALESCE(SUM(a.quantiteAttribuee), 0)) AS quantiteNonSatisfaite
FROM
    bngrc_villes v
JOIN
    bngrc_besoins b ON v.id = b.idVille
LEFT JOIN
    bngrc_attributions a 
        ON b.id = a.idBesoin   -- ⚠ correction importante ici
GROUP BY
    v.id, b.id
HAVING
    quantiteNonSatisfaite > 0;

SELECT * FROM vue_besoins_non_satisfaits;


CREATE OR REPLACE VIEW vue_besoins_non_satisfaits AS
SELECT
    v.nom AS nomVille,
    v.region AS regionVille,
    b.id AS idBesoin,
    b.type AS typeBesoin,
    b.designation AS designationBesoin,
    b.quantite AS quantiteBesoin,
    COALESCE(SUM(a.quantiteAttribuee), 0) AS quantiteAttribuee,
    (b.quantite - COALESCE(SUM(a.quantiteAttribuee), 0)) AS quantiteNonSatisfaite
FROM
    bngrc_villes v
JOIN
    bngrc_besoins b ON v.id = b.idVille
LEFT JOIN
    bngrc_attributions a 
        ON b.idVille = a.idVille
        AND b.designation = a.designation
GROUP BY
    v.id, b.id
HAVING
    quantiteNonSatisfaite > 0;
SELECT * FROM vue_besoins_non_satisfaits;
