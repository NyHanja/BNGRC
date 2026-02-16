@ -1,26 +0,0 @@
<div class="page-section">
    <div class="page-header">
        <h2><?php echo isset($ville) ? 'Modifier une ville' : 'Ajouter une ville'; ?></h2>
    </div>

    <form method="POST" action="/villes/save" class="form">
        <?php if(isset($ville)): ?>
        <input type="hidden" name="id" value="<?php echo $ville['id']; ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="nom">Nom de la ville:</label>
            <input type="text" id="nom" name="nom" class="form-control" value="<?php echo isset($ville) ? htmlspecialchars($ville['nom']) : ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="region">Région:</label>
            <input type="text" id="region" name="region" class="form-control" value="<?php echo isset($ville) ? htmlspecialchars($ville['region']) : ''; ?>" required>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
            <a href="/villes" class="btn btn-secondary">❌ Annuler</a>
        </div>
    </form>
</div>