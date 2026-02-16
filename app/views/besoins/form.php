<div class="page-section">
    <div class="page-header">
        <h2><?php echo isset($besoin) ? 'Modifier un besoin' : 'Ajouter un besoin'; ?></h2>
    </div>

    <form method="POST" action="/besoins/save" class="form">
        <?php if(isset($besoin)): ?>
        <input type="hidden" name="id" value="<?php echo $besoin['id']; ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="idVille">Ville:</label>
            <select id="idVille" name="idVille" class="form-control" required>
                <option value="">-- Sélectionner une ville --</option>
                <?php foreach($villes as $ville): ?>
                <option value="<?php echo $ville['id']; ?>" <?php echo (isset($besoin) && $besoin['idVille'] == $ville['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($ville['nom']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="type">Type:</label>
            <select id="type" name="type" class="form-control" required>
                <option value="nature" <?php echo (isset($besoin) && $besoin['type'] === 'nature') ? 'selected' : ''; ?>>Nature</option>
                <option value="materiaux" <?php echo (isset($besoin) && $besoin['type'] === 'materiaux') ? 'selected' : ''; ?>>Matériaux</option>
                <option value="argent" <?php echo (isset($besoin) && $besoin['type'] === 'argent') ? 'selected' : ''; ?>>Argent</option>
            </select>
        </div>

        <div class="form-group">
            <label for="designation">Désignation:</label>
            <input type="text" id="designation" name="designation" class="form-control" value="<?php echo isset($besoin) ? htmlspecialchars($besoin['designation']) : ''; ?>" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="prixUnitaire">Prix Unitaire (Ar):</label>
                <input type="number" id="prixUnitaire" name="prixUnitaire" class="form-control" step="0.01" value="<?php echo isset($besoin) ? $besoin['prixUnitaire'] : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="quantite">Quantité:</label>
                <input type="number" id="quantite" name="quantite" class="form-control" value="<?php echo isset($besoin) ? $besoin['quantite'] : ''; ?>" required>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
            <a href="besoins" class="btn btn-secondary">❌ Annuler</a>
        </div>
    </form>
</div>
