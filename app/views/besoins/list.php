<div class="page-section">
    <div class="page-header">
        <h2>Gestion des Besoins</h2>
        <a href="besoins/create" class="btn btn-primary">➕ Ajouter un besoin</a>
    </div>

    <?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-success">
        <?php
        $msg = $_GET['msg'];
        if($msg === 'created') echo 'Besoin créé avec succès!';
        elseif($msg === 'updated') echo 'Besoin modifié avec succès!';
        elseif($msg === 'deleted') echo 'Besoin supprimé avec succès!';
        ?>
    </div>
    <?php endif; ?>

    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Ville</th>
                <th>Type</th>
                <th>Désignation</th>
                <th>Prix Unitaire</th>
                <th>Quantité</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($besoins as $besoin): ?>
            <tr>
                <td><?php echo $besoin['id']; ?></td>
                <td><?php echo htmlspecialchars($besoin['ville'] ?? 'N/A'); ?></td>
                <td><span class="badge badge-<?php echo $besoin['type']; ?>"><?php echo ucfirst($besoin['type']); ?></span></td>
                <td><?php echo htmlspecialchars($besoin['designation']); ?></td>
                <td><?php echo number_format($besoin['prixUnitaire'], 2); ?> Ar</td>
                <td><?php echo $besoin['quantite']; ?></td>
                <td><?php echo $besoin['dateSaisie']; ?></td>
                <td>
                    <a href="besoins/<?php echo $besoin['id']; ?>/edit" class="btn btn-sm btn-warning">✏️ Éditer</a>
                    <form method="POST" action="besoins/<?php echo $besoin['id']; ?>/delete" style="display:inline;">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr?')">🗑️ Supprimer</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
