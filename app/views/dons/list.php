<div class="page-section">
    <div class="page-header">
        <h2>Gestion des Dons</h2>
        <a href="<?php echo Flight::get('flight.base_url'); ?>dons/create" class="btn btn-primary">➕ Ajouter un don</a>
    </div>

    <?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-<?php echo in_array($_GET['msg'], ['dispatch_error', 'undispatch_error', 'error']) ? 'danger' : 'success'; ?>">
        <?php
        $msg = $_GET['msg'];
        if($msg === 'created') echo 'Don créé avec succès!';
        elseif($msg === 'updated') echo 'Don modifié avec succès!';
        elseif($msg === 'deleted') echo 'Don supprimé avec succès!';
        elseif($msg === 'dispatched') echo '✅ ' . htmlspecialchars($_GET['detail'] ?? 'Dons dispatchés avec succès!');
        elseif($msg === 'undispatched') echo '↩️ Tous les dispatches ont été annulés. Les dons sont disponibles.';
        elseif($msg === 'dispatch_error') echo '❌ Erreur dispatch: ' . htmlspecialchars($_GET['detail'] ?? 'Erreur inconnue');
        elseif($msg === 'undispatch_error') echo '❌ Erreur annulation: ' . htmlspecialchars($_GET['detail'] ?? 'Erreur inconnue');
        ?>
    </div>
    <?php endif; ?>

    <!-- Boutons de dispatch globaux -->
    <div class="dispatch-actions">
        <form method="POST" action="<?php echo Flight::get('flight.base_url'); ?>dons/dispatch-tous" style="display:inline;">
            <button type="submit" class="btn btn-success">
                🚀 Dispatcher tous (besoin le plus ancien)
            </button>
        </form>
        <form method="POST" action="<?php echo Flight::get('flight.base_url'); ?>dons/dispatch-plus-petit" style="display:inline;">
            <button type="submit" class="btn btn-primary">
                📊 Dispatcher tous (plus petit besoin d'abord)
            </button>
        </form>
        <form method="POST" action="<?php echo Flight::get('flight.base_url'); ?>dons/dispatch-proportionnel" style="display:inline;">
            <button type="submit" class="btn btn-info">
                ⚖️ Dispatcher tous (proportionnel)
            </button>
        </form>
        <form method="POST" action="<?php echo Flight::get('flight.base_url'); ?>dons/annuler-tous-dispatches" style="display:inline;">
            <button type="submit" class="btn btn-danger">
                ↩️ Annuler tous les dispatches
            </button>
        </form>
    </div>

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
                <th>Statut</th>
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
                    <?php if(!empty($don['dispatched'])): ?>
                        <span class="badge badge-dispatched">✅ Dispatché</span>
                    <?php else: ?>
                        <span class="badge badge-pending">⏳ En attente</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?php echo Flight::get('flight.base_url'); ?>dons/<?php echo $don['id']; ?>/rapport" class="btn btn-sm btn-info" title="Voir le rapport">📊 Rapport</a>
                    <a href="<?php echo Flight::get('flight.base_url'); ?>dons/<?php echo $don['id']; ?>/edit" class="btn btn-sm btn-warning">✏️ Éditer</a>
                    <form method="POST" action="<?php echo Flight::get('flight.base_url'); ?>dons/<?php echo $don['id']; ?>/delete" style="display:inline;">
                        <button type="submit" class="btn btn-sm btn-danger">🗑️ Supprimer</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
