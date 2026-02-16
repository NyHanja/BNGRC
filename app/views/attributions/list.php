<div class="page-section">
    <div class="page-header">
        <h2>Gestion des Attributions</h2>
        <a href="<?php echo Flight::get('flight.base_url'); ?>attributions/create" class="btn btn-primary">➕ Ajouter une attribution</a>
    </div>

    <?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-success">
        <?php
        $msg = $_GET['msg'];
        if($msg === 'created') echo 'Attribution créée avec succès!';
        elseif($msg === 'updated') echo 'Attribution modifiée avec succès!';
        elseif($msg === 'deleted') echo 'Attribution supprimée avec succès!';
        ?>
    </div>
    <?php endif; ?>

    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Donateur</th>
                <th>Ville Bénéficiaire</th>
                <th>Désignation</th>
                <th>Quantité Attribuée</th>
                <th>Date Attribution</th>
                <th>Type</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($attributions as $attribution): ?>
            <tr>
                <td><?php echo $attribution['id']; ?></td>
                <td><?php echo htmlspecialchars($attribution['donateur'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($attribution['ville'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($attribution['designation']); ?></td>
                <td><?php echo $attribution['quantiteAttribuee']; ?></td>
                <td><?php echo $attribution['dateAttribution']; ?></td>
                <td><span class="badge badge-<?php echo $attribution['type'] ?? 'nature'; ?>"><?php echo ucfirst($attribution['type'] ?? 'N/A'); ?></span></td>
                <td>
                    <a href="<?php echo Flight::get('flight.base_url'); ?>attributions/<?php echo $attribution['id']; ?>/edit" class="btn btn-sm btn-warning">✏️ Éditer</a>
                    <form method="POST" action="<?php echo Flight::get('flight.base_url'); ?>attributions/<?php echo $attribution['id']; ?>/delete" style="display:inline;">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr?')">🗑️ Supprimer</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
