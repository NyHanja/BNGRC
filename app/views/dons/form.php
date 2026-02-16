<div class="page-section">
    <div class="page-header">
        <h2><?php echo isset($don) ? 'Modifier un don' : 'Ajouter un don'; ?></h2>
    </div>

    <form method="POST" action="/dons/save" class="form">
        <?php if(isset($don)): ?>
        <input type="hidden" name="id" value="<?php echo $don['id']; ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="donateur">Donateur:</label>
            <input type="text" id="donateur" name="donateur" class="form-control" value="<?php echo isset($don) ? htmlspecialchars($don['donateur']) : ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="type">Type:</label>
            <select id="type" name="type" class="form-control" required>
                <option value="nature" <?php echo (isset($don) && $don['type'] === 'nature') ? 'selected' : ''; ?>>Nature</option>
                <option value="materiaux" <?php echo (isset($don) && $don['type'] === 'materiaux') ? 'selected' : ''; ?>>Matériaux</option>
                <option value="argent


CREATE OR REPLACE VIEW vue_besoins_non_satisfaits AS
SELECT
    v.nom AS nomVille,
    v.region AS regionVille,
    b.id AS idBesoin,
    b.type AS typeBesoin,
    b.designation AS designationBesoin,
    b.quantite AS quantiteBesoin,
    COALESCE(SUM(a.quantiteAttribuee), 0) AS quantiteAttribuee,
    (b.quantite - COALESCE(SUM(a.quantiteAttribuee), 0)) AS quantiteNonSatisfaite,
    b.prixUnitaire*COALESCE(SUM(a.quantiteAttribuee), 0) AS MontantBesoinAttribue,
    b.prixUnitaire*b.quantite AS MontantBesoin,
    b.prixUnitaire*(b.quantite - COALESCE(SUM(a.quantiteAttribuee), 0)) AS MontantBesoinNonSatisfait,
    (SELECT SUM(b2.prixUnitaire * COALESCE(SUM(a2.quantiteAttribuee), 0)) 
     FROM bngrc_besoins b2 
     JOIN bngrc_attributions a2 ON b2.idVille = a2.idVille AND b2.designation = a2.designation
    ) AS totalMontantBesoinAttribue

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
    quantiteNonSatisfaite > 0;" <?php echo (isset($don) && $don['type'] === 'argent') ? 'selected' : ''; ?>>Argent</option>
            </select>
        </div>

        <div class="form-group">
            <label for="designation">Désignation:</label>
            <input type="text" id="designation" name="designation" class="form-control" value="<?php echo isset($don) ? htmlspecialchars($don['designation']) : ''; ?>" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="montantUnitaire">Montant Unitaire (Ar):</label>
                <input type="number" id="montantUnitaire" name="montantUnitaire" class="form-control" step="0.01" value="<?php echo isset($don) ? $don['montantUnitaire'] : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="quantite">Quantité:</label>
                <input type="number" id="quantite" name="quantite" class="form-control" value="<?php echo isset($don) ? $don['quantite'] : ''; ?>" required>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
            <a href="dons" class="btn btn-secondary">❌ Annuler</a>
        </div>
    </form>
</div>
