<?php
$historique = $historique ?? [];
?>

<div class="page-section">
    <div class="page-header">
        <h2>📋 Historique des Répartitions d'Argent</h2>
    </div>

    <?php if (!empty($historique)): ?>
    <div style="margin-top: 1.5rem;">
        <?php foreach ($historique as $repartition): ?>
        <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #3498db;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <div>
                    <h4 style="margin: 0; color: #2c3e50;">
                        🏥 Besoin #<strong><?php echo htmlspecialchars($repartition['idBesoin']); ?></strong>
                    </h4>
                    <small style="color: #7f8c8d;">
                        📅 <?php 
                            $date = new DateTime($repartition['dateRepartition']);
                            echo $date->format('d/m/Y à H:i:s');
                        ?>
                    </small>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 1.3rem; font-weight: 700; color: #e74c3c;">
                        💰 <?php echo number_format($repartition['montantReparti'], 0, ',', ' '); ?> Ar
                    </div>
                    <small style="color: #7f8c8d;">Montant réparti</small>
                </div>
            </div>

            <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-top: 1rem;">
                <strong style="display: block; margin-bottom: 0.5rem;">🏙️ Distribution par Ville:</strong>
                <small style="color: #555;">
                    <?php echo htmlspecialchars($repartition['details']); ?>
                </small>
            </div>

            <div style="margin-top: 0.5rem; font-size: 0.85rem; color: #95a5a6;">
                ✓ Nombre de villes bénéficiaires: <strong><?php echo $repartition['nbVillesRecipients']; ?></strong>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="background: #d5f4e6; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #27ae60; color: #155724;">
        <strong>✓ Aucune répartition d'argent</strong> - Aucun besoin d'argent n'a été complètement satisfait pour le moment.
    </div>
    <?php endif; ?>

    <!-- Lien de retour -->
    <div style="margin-top: 2rem;">
        <a href="<?php echo Flight::get('flight.base_url'); ?>stock-argent" class="btn btn-primary" style="display: inline-block; padding: 0.75rem 1.5rem; background-color: #3498db; color: white; text-decoration: none; border-radius: 4px;">
            ← Retour aux Stocks
        </a>
    </div>
</div>

<style>
    .btn {
        display: inline-block;
        padding: 0.75rem 1.5rem;
        border-radius: 4px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-primary {
        background-color: #3498db;
        color: white;
    }

    .btn-primary:hover {
        background-color: #2980b9;
        text-decoration: none;
        color: white;
    }
</style>
