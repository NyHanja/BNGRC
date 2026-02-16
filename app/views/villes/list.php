<div class="page-section">
    <div class="page-header">
        <h2>Gestion des Villes</h2>
        <a href="<?php echo Flight::get('flight.base_url'); ?>villes/create" class="btn btn-primary">➕ Ajouter une ville</a>
    </div>

    <?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-success">
        <?php
        $msg = $_GET['msg'];
        if($msg === 'created') echo 'Ville créée avec succès!';
        elseif($msg === 'updated') echo 'Ville modifiée avec succès!';
        elseif($msg === 'deleted') echo 'Ville supprimée avec succès!';
        ?>
    </div>
    <?php endif; ?>

    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Région</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($villes as $ville): ?>
            <tr>
                <td><?php echo $ville['id']; ?></td>
                <td><?php echo htmlspecialchars($ville['nom']); ?></td>
                <td><?php echo htmlspecialchars($ville['region']); ?></td>
                <td>
                    <a href="<?php echo Flight::get('flight.base_url'); ?>villes/<?php echo $ville['id']; ?>/edit" class="btn btn-sm btn-warning">✏️ Éditer</a>
                    <form method="POST" action="<?php echo Flight::get('flight.base_url'); ?>villes/<?php echo $ville['id']; ?>/delete" style="display:inline;">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr?')">🗑️ Supprimer</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
