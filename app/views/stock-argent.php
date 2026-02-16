<?php
$nonce = Flight::get('csp_nonce');
?>

<div class="page-section">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2>💰 Suivi du Stock d'Argent par Ville</h2>
        <form method="POST" action="<?php echo Flight::get('flight.base_url'); ?>stock-argent/redistribuer-tout" style="display:inline;">
            <button type="submit" class="btn btn-primary" onclick="return confirm('Recalculer et redistribuer tous les dons d\'argent ?')">
                🔄 Redistribuer tous les dons d'argent
            </button>
        </form>
    </div>

    <?php if (!empty($stocks)): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>🏙️ Ville</th>
                <th class="text-right">💵 Montant Disponible (Ar)</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($stocks as $stock): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($stock['villeName'] ?? 'N/A'); ?></strong></td>
                <td class="text-right">
                    <span class="badge" style="background-color: #27ae60; color: white; padding: 0.5rem 1rem; border-radius: 4px;">
                        <?php echo number_format($stock['quantite'], 0, ',', ' '); ?> Ar
                    </span>
                </td>
                <td class="text-center">
                    <a href="<?php echo Flight::get('flight.base_url'); ?>stock-argent/<?php echo $stock['idVille']; ?>/details" class="btn btn-sm btn-info">
                        👁️ Détails
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top: 2rem; padding: 1.5rem; background-color: #ecf0f1; border-radius: 8px;">
        <h3>📊 Résumé des Stocks</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-top: 1rem;">
            <div style="background: white; padding: 1rem; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div style="font-size: 0.9rem; color: #7f8c8d; margin-bottom: 0.5rem;">Total Argent en Stock</div>
                <div style="font-size: 1.8rem; font-weight: 700; color: #27ae60;">
                    <?php echo number_format(array_sum(array_column($stocks, 'quantite')), 0, ',', ' '); ?> Ar
                </div>
            </div>
            <div style="background: white; padding: 1rem; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div style="font-size: 0.9rem; color: #7f8c8d; margin-bottom: 0.5rem;">Nombre de Villes</div>
                <div style="font-size: 1.8rem; font-weight: 700; color: #3498db;">
                    <?php echo count($stocks); ?>
                </div>
            </div>
            <div style="background: white; padding: 1rem; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div style="font-size: 0.9rem; color: #7f8c8d; margin-bottom: 0.5rem;">Montant Moyen par Ville</div>
                <div style="font-size: 1.8rem; font-weight: 700; color: #f39c12;">
                    <?php echo number_format(array_sum(array_column($stocks, 'quantite')) / max(1, count($stocks)), 0, ',', ' '); ?> Ar
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <div class="alert alert-info">
        <strong>ℹ️ Info</strong>
        <p>Aucun stock d'argent n'a été enregistré pour le moment.</p>
        <p>Les dons d'argent apparaîtront ici une fois reçus et attribués à des villes.</p>
    </div>
    <?php endif; ?>
</div>
