<?php
$nonce = Flight::get('csp_nonce');
?>

<div class="simulation-container">
    <div class="simulation-header">
        <h1>🧪 Simulation de Distribution</h1>
        <p>Testez l'impact des distributions avant validation</p>
    </div>

    <div class="simulation-grid">
        <!-- Colonne gauche: Formulaire -->
        <div class="simulation-form-section">
            <div class="card">
                <h2>📋 Sélectionner un besoin</h2>
                
                <?php if (!empty($besoinsNonSatisfaits)): ?>
                <form id="simulationForm">
                    <div class="form-group">
                        <label for="besoinSelect">Besoin à satisfaire:</label>
                        <select id="besoinSelect" name="besoinId" class="form-control" required>
                            <option value="">-- Choisir un besoin --</option>
                            <?php foreach ($besoinsNonSatisfaits as $besoin): ?>
                            <option value="<?php echo $besoin['idBesoin']; ?>" 
                                    data-quantite-non-satisfaite="<?php echo $besoin['quantiteNonSatisfaite']; ?>"
                                    data-montant-restant="<?php echo $besoin['MontantBesoinNonSatisfait']; ?>">
                                🏙️ <?php echo htmlspecialchars($besoin['nomVille']); ?> - 
                                <?php echo htmlspecialchars($besoin['designationBesoin']); ?>
                                (<?php echo $besoin['quantiteNonSatisfaite']; ?> restant)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text">Sélectionnez un besoin non satisfait</small>
                    </div>

                    <div class="form-group">
                        <label for="quantite">Quantité à attribuer:</label>
                        <input type="number" id="quantite" name="quantite" min="1" value="1" class="form-control" required>
                        <small class="form-text" id="quantiteInfo"></small>
                    </div>

                    <div class="form-group">
                        <label for="tauxFrais">Taux de frais (%):</label>
                        <input type="number" id="tauxFrais" name="frais" min="0" max="100" step="0.1" value="5" class="form-control">
                        <small class="form-text">Frais de traitement (par défaut 5%)</small>
                    </div>

                    <div class="form-actions">
                        <button type="button" id="btnSimuler" class="btn btn-info">
                            <span class="btn-icon">🧪</span> Simuler
                        </button>
                        <button type="button" id="btnValider" class="btn btn-success" disabled>
                            <span class="btn-icon">✅</span> Valider
                        </button>
                    </div>
                </form>

                <?php else: ?>
                <div class="alert alert-success">
                    <strong>✅ Excellent!</strong> Tous les besoins ont été satisfaits. Aucune simulation à faire.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Colonne droite: Résultats -->
        <div class="simulation-results-section">
            <div class="card">
                <h2>📊 Résultats de la Simulation</h2>
                
                <div id="resultatContainer" class="resultat-empty">
                    <div class="empty-state">
                        <p class="empty-icon">🔍</p>
                        <p class="empty-text">Cliquez sur "Simuler" pour voir l'aperçu</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo Flight::get('flight.base_url'); ?>simulation.js" nonce="<?=$nonce?>"></script>
