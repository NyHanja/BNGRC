<?php
$nonce = Flight::get('csp_nonce');
?>

<div class="recap-container">
    <div class="recap-header">
        <h1>📊 Récapitulatif du Système</h1>
        <p>Vue d'ensemble des dons, besoins et attributions</p>
    </div>

    <div class="recap-stats">
        <div class="stat-card">
            <div class="stat-icon">🏙️</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $totalVilles; ?></div>
                <div class="stat-label">Villes</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $totalBesoins; ?></div>
                <div class="stat-label">Besoins</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🎁</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $totalDons; ?></div>
                <div class="stat-label">Dons</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $totalAttributions; ?></div>
                <div class="stat-label">Attributions</div>
            </div>
        </div>
    </div>

    <div class="recap-actions">
        <button id="btnRafraichir" class="btn btn-primary">
            <span class="btn-icon">🔄</span> Rafraîchir les données
        </button>
        <button id="btnExporter" class="btn btn-secondary">
            <span class="btn-icon">📥</span> Exporter
        </button>
    </div>

    <div id="recap-content" class="recap-content">
        <!-- Besoins non satisfaits statiques -->
        <?php if (!empty($besoinsNonSatisfaits)): ?>
        <div class="recap-section">
            <h2>⚠️ Besoins Non Satisfaits (<?php echo count($besoinsNonSatisfaits); ?>)</h2>
            <table class="recap-table">
                <thead>
                    <tr>
                        <th>Ville</th>
                        <th>Région</th>
                        <th>Type</th>
                        <th>Désignation</th>
                        <th class="text-center">Quantité Besoin</th>
                        <th class="text-center">Attribuée</th>
                        <th class="text-center">Non Satisfaite</th>
                        <th class="text-right">Montant Restant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($besoinsNonSatisfaits as $besoin): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($besoin['nomVille']); ?></strong></td>
                        <td><?php echo htmlspecialchars($besoin['regionVille']); ?></td>
                        <td>
                            <?php 
                            $types = ['nature' => '🌾', 'materiaux' => '🏗️', 'argent' => '💵'];
                            echo $types[$besoin['typeBesoin']] ?? '';
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($besoin['designationBesoin']); ?></td>
                        <td class="text-center"><?php echo $besoin['quantiteBesoin']; ?></td>
                        <td class="text-center"><?php echo $besoin['quantiteAttribuee']; ?></td>
                        <td class="text-center"><strong style="color: #e74c3c;"><?php echo $besoin['quantiteNonSatisfaite']; ?></strong></td>
                        <td class="text-right"><strong><?php echo number_format($besoin['MontantBesoinNonSatisfait'], 2); ?> Ar</strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="recap-section">
            <div class="alert alert-success">
                <strong>✅ Excellent!</strong> Tous les besoins ont été satisfaits.
            </div>
        </div>
        <?php endif; ?>

        <!-- Contenu dynamique chargé via AJAX -->
        <div id="ajax-content" class="loading-spinner">
            <div class="spinner"></div>
            <p>Chargement des statistiques détaillées...</p>
        </div>
    </div>
</div>

<script src="<?php echo Flight::get('flight.base_url'); ?>recap.js" nonce="<?=$nonce?>"></script>
