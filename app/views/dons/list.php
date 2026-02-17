<div class="page-section">
    <div class="page-header">
        <h2>Gestion des Dons</h2>
        <a href="dons/create" class="btn btn-primary">➕ Ajouter un don</a>
    </div>

    <?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-success">
        <?php
        $msg = $_GET['msg'];
        if($msg === 'created') echo 'Don créé avec succès!';
        elseif($msg === 'updated') echo 'Don modifié avec succès!';
        elseif($msg === 'deleted') echo 'Don supprimé avec succès!';
        ?>
    </div>
    <?php endif; ?>

    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Donateur</th>
                <th>Type</th>
                <th>Désignation</th>
                <th>Montant Unitaire</th>
                <th>Quantité</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($dons as $don): ?>
            <tr>
                <td><?php echo $don['id']; ?></td>
                <td><?php echo htmlspecialchars($don['donateur']); ?></td>
                <td><span class="badge badge-<?php echo $don['type']; ?>"><?php echo ucfirst($don['type']); ?></span></td>
                <td><?php echo htmlspecialchars($don['designation']); ?></td>
                <td><?php echo number_format($don['montantUnitaire'], 2); ?> Ar</td>
                <td><?php echo $don['quantite']; ?></td>
                <td><?php echo $don['dateSaisie']; ?></td>
                <td>
                    <a href="dons/<?php echo $don['id']; ?>/rapport" class="btn btn-sm btn-info" title="Voir le rapport de distribution">📊 Rapport</a>
                    <a href="dons/<?php echo $don['id']; ?>/edit" class="btn btn-sm btn-warning">✏️ Éditer</a>
                    <form method="POST" action="dons/<?php echo $don['id']; ?>/delete" style="display:inline;">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr?')">🗑️ Supprimer</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
