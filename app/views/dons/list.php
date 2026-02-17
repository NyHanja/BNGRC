<div class="page-section">
    <div class="page-header">
        <h2>Gestion des Dons</h2>
        <a href="dons/create" class="btn btn-primary">➕ Ajouter un don</a>
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
        <form method="POST" action="dons/dispatch-tous" style="display:inline;">
            <button type="submit" class="btn btn-success">
                🚀 Dispatcher (ancien)
            </button>
        </form>
        <form method="POST" action="dons/dispatch-plus-petit" style="display:inline;">
            <button type="submit" class="btn btn-primary">
                📊 Dispatcher (plus petit)
            </button>
        </form>
        <form method="POST" action="dons/dispatch-proportionnel" style="display:inline;">
            <button type="submit" class="btn btn-info">
                ⚖️ Dispatcher (proportionnel)
            </button>
        </form>
        <form method="POST" action="dons/annuler-tous-dispatches" style="display:inline;">
            <button type="submit" class="btn btn-danger">
                ↩️ Annuler tous
            </button>
        </form>
    </div>

    <table class="data-table dons-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Donateur</th>
                <th>Type</th>
                <th>Désignation</th>
                <th>Montant Unitaire</th>
                <th>Quantité</th>
                <th>Date</th>
                <th>Statut</th>
                <th>Stock</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($dons as $don): ?>
            <tr>
                <td><?php echo $don['id']; ?></td>
                <td class="td-donateur" title="<?php echo htmlspecialchars($don['donateur']); ?>"><?php echo htmlspecialchars($don['donateur']); ?></td>
                <td><span class="badge badge-<?php echo $don['type']; ?>"><?php echo ucfirst($don['type']); ?></span></td>
                <td><?php echo htmlspecialchars($don['designation']); ?></td>
                <td class="td-montant"><?php echo number_format($don['montantUnitaire'], 0, ',', ' '); ?> Ar</td>
                <td><?php echo number_format($don['quantite'], 0, ',', ' '); ?></td>
                <td class="td-date"><?php echo date('d/m/Y', strtotime($don['dateSaisie'])); ?></td>
                <td>
                    <?php if(!empty($don['dispatched'])): ?>
                        <span class="badge badge-dispatched">✅ Dispatché</span>
                    <?php else: ?>
                        <span class="badge badge-pending">⏳ En attente</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php 
                        $stock = (int)($don['stock'] ?? 0);
                        if ($stock > 0): 
                    ?>
                        <span class="badge badge-stock"><?php echo number_format($stock, 0, ',', ' '); ?></span>
                    <?php else: ?>
                        <span class="td-stock-zero">—</span>
                    <?php endif; ?>
                </td>
                <td class="td-actions">
                    <a href="dons/<?php echo $don['id']; ?>/rapport" class="btn btn-sm btn-info" title="Rapport">📊</a>
                    <a href="dons/<?php echo $don['id']; ?>/edit" class="btn btn-sm btn-warning" title="Éditer">✏️</a>
                    <form method="POST" action="dons/<?php echo $don['id']; ?>/delete" style="display:inline;">
                        <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">🗑️</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
