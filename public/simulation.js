/**
 * Simulation.js - Gestion de la simulation de distribution
 */

let dernierSimulation = null;

document.addEventListener('DOMContentLoaded', () => {
    // Event listeners
    document.getElementById('besoinSelect')?.addEventListener('change', onBesoinChange);
    document.getElementById('quantite')?.addEventListener('change', onQuantiteChange);
    document.getElementById('btnSimuler').addEventListener('click', simulerDistribution);
    document.getElementById('btnValider').addEventListener('click', validerDistribution);
});

/**
 * Quand le besoin change
 */
function onBesoinChange() {
    const select = document.getElementById('besoinSelect');
    const quantiteInput = document.getElementById('quantite');
    const quantiteInfo = document.getElementById('quantiteInfo');
    
    if (select.value) {
        const option = select.options[select.selectedIndex];
        const maxQuantite = option.dataset.quantiteNonSatisfaite;
        quantiteInput.max = maxQuantite;
        quantiteInput.value = 1;
        quantiteInfo.textContent = `Maximum disponible: ${maxQuantite}`;
        
        // Réinitialiser les résultats
        resetResultats();
    }
}

/**
 * Quand la quantité change
 */
function onQuantiteChange() {
    const quantiteInput = document.getElementById('quantite');
    const maxQuantite = quantiteInput.max;
    
    if (parseInt(quantiteInput.value) > parseInt(maxQuantite)) {
        quantiteInput.value = maxQuantite;
    }
}

/**
 * Simuler la distribution (sans modification)
 */
function simulerDistribution() {
    const besoinId = document.getElementById('besoinSelect').value;
    const quantite = document.getElementById('quantite').value;
    const frais = document.getElementById('tauxFrais').value;

    if (!besoinId) {
        afficherErreur('Veuillez sélectionner un besoin');
        return;
    }

    // Appel AJAX
    fetch(`${window.BASE_URL || '/'}api/simulation?besoinId=${besoinId}&quantite=${quantite}&frais=${frais / 100}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                dernierSimulation = data;
                afficherResultats(data);
                // Désactiver le bouton valider si stock insuffisant
                const btnValider = document.getElementById('btnValider');
                if (!data.simulation.stockSuffisant) {
                    btnValider.disabled = true;
                    btnValider.title = 'Stock d\'argent insuffisant pour cette ville';
                } else {
                    btnValider.disabled = false;
                    btnValider.title = '';
                }
            } else {
                afficherErreur(data.message || 'Erreur lors de la simulation');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            afficherErreur(`Erreur: ${error.message}`);
        });
}

/**
 * Valider et enregistrer
 */
function validerDistribution() {
    if (!dernierSimulation) {
        afficherErreur('Aucune simulation à valider');
        return;
    }

    if (!confirm('⚠️ Êtes-vous sûr de vouloir valider cette distribution?\n\nCette action enregistrera définitivement les changements.')) {
        return;
    }

    const formData = new FormData();
    formData.append('besoinId', document.getElementById('besoinSelect').value);
    formData.append('quantite', dernierSimulation.simulation.quantiteAAttribuer);
    formData.append('montantNet', dernierSimulation.simulation.montantNet);

    fetch((window.BASE_URL || '/') + 'api/simulation/valider', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            afficherSucces(data.message);
            resetFormulaire();
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            afficherErreur(data.message || 'Erreur lors de la validation');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        afficherErreur(`Erreur: ${error.message}`);
    });
}

/**
 * Afficher les résultats de la simulation
 */
function afficherResultats(data) {
    const sim = data.simulation;
    const besoin = data.besoin;
    
    let html = `
        <div class="resultat-content">
            <div class="resultat-section besoin-info">
                <h3>📋 Besoin cible</h3>
                <div class="info-table">
                    <div class="info-row">
                        <span class="info-label">Ville:</span>
                        <span class="info-value">${echapperHTML(besoin.nomVille)}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Designation:</span>
                        <span class="info-value">${echapperHTML(besoin.designationBesoin)}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Type:</span>
                        <span class="info-value">${getTypeIcon(besoin.typeBesoin)} ${besoin.typeBesoin}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Quantité restante:</span>
                        <span class="info-value" style="color: #e74c3c;">${sim.quantiteDisponible}</span>
                    </div>
                </div>
            </div>

            <div class="resultat-section calculs">
                <h3>💰 Calculs</h3>
                <div class="calcul-row" style="background: ${sim.stockSuffisant ? '#eafaf1' : '#fdedec'}; padding: 0.5rem; border-radius: 4px; margin-bottom: 0.5rem;">
                    <span class="calcul-label">💰 Stock argent de ${echapperHTML(sim.villeNom || besoin.nomVille)}:</span>
                    <span class="calcul-value" style="color: ${sim.stockSuffisant ? '#27ae60' : '#e74c3c'}; font-weight: bold;">
                        ${formatMontant(sim.stockDisponible)} ${sim.stockSuffisant ? '✅ Suffisant' : '❌ Insuffisant'}
                    </span>
                </div>
                <div class="calcul-row">
                    <span class="calcul-label">Quantité demandée:</span>
                    <span class="calcul-value">${sim.quantiteVoulue}</span>
                </div>
                <div class="calcul-row">
                    <span class="calcul-label">Quantité disponible:</span>
                    <span class="calcul-value">${sim.quantiteDisponible}</span>
                </div>
                <div class="calcul-row highlight">
                    <span class="calcul-label">Quantité à attribuer:</span>
                    <span class="calcul-value">${sim.quantiteAAttribuer}</span>
                </div>

                <div class="calcul-divider"></div>

                <div class="calcul-row">
                    <span class="calcul-label">Montant unitaire:</span>
                    <span class="calcul-value">${formatMontant(sim.montantUnitaire)}</span>
                </div>
                <div class="calcul-row">
                    <span class="calcul-label">Montant brut:</span>
                    <span class="calcul-value">${formatMontant(sim.montantBrut)}</span>
                </div>
                <div class="calcul-row">
                    <span class="calcul-label">Frais (${sim.tauxFrais}%):</span>
                    <span class="calcul-value orange">${formatMontant(sim.frais)}</span>
                </div>
                <div class="calcul-row highlight total">
                    <span class="calcul-label">Total NET:</span>
                    <span class="calcul-value">${formatMontant(sim.montantNet)}</span>
                </div>
            </div>

            <div class="resultat-section impact">
                <h3>📊 Impact</h3>
                <div class="impact-bar">
                    <div class="impact-item satisfied">
                        <span class="impact-label">Satisfait:</span>
                        <span class="impact-value">${sim.quantiteAAttribuer}</span>
                    </div>
                    <div class="impact-item remaining">
                        <span class="impact-label">Restant:</span>
                        <span class="impact-value">${sim.quantiteRestante}</span>
                    </div>
                </div>
                <div style="margin-top: 1rem;">
                    <strong>Montant restant après:</strong> ${formatMontant(sim.montantRestant)}
                </div>
                ${sim.sourceFinancement === 'stockArgent' ? `
                <div style="margin-top: 0.5rem;">
                    <strong>💰 Stock argent après validation:</strong> 
                    <span style="color: ${sim.stockSuffisant ? '#27ae60' : '#e74c3c'}; font-weight: bold;">
                        ${formatMontant(sim.stockDisponible - sim.montantNet)}
                    </span>
                </div>
                ` : ''}
            </div>

            <div class="alert alert-info">
                <strong>ℹ️ Info</strong>
                <p>Ceci est un aperçu. Cliquez sur "Valider" pour enregistrer définitivement.</p>
            </div>
        </div>
    `;

    document.getElementById('resultatContainer').innerHTML = html;
}

/**
 * Afficher erreur
 */
function afficherErreur(message) {
    const html = `
        <div class="alert alert-danger">
            <strong>⚠️ Erreur</strong>
            <p>${echapperHTML(message)}</p>
        </div>
    `;
    document.getElementById('resultatContainer').innerHTML = html;
}

/**
 * Afficher succès
 */
function afficherSucces(message) {
    alert(`✅ ${message}`);
}

/**
 * Réinitialiser les résultats
 */
function resetResultats() {
    document.getElementById('resultatContainer').innerHTML = `
        <div class="empty-state">
            <p class="empty-icon">🔍</p>
            <p class="empty-text">Cliquez sur "Simuler" pour voir l'aperçu</p>
        </div>
    `;
    document.getElementById('btnValider').disabled = true;
    dernierSimulation = null;
}

/**
 * Réinitialiser le formulaire
 */
function resetFormulaire() {
    document.getElementById('simulationForm').reset();
    resetResultats();
}

/**
 * Formater un montant
 */
function formatMontant(montant) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'MGA',
        minimumFractionDigits: 0
    }).format(montant);
}

/**
 * Obtenir l'icône du type
 */
function getTypeIcon(type) {
    const icons = {
        'nature': '🌾',
        'materiaux': '🏗️',
        'argent': '💵'
    };
    return icons[type] || '📦';
}

/**
 * Échapper HTML
 */
function echapperHTML(texte) {
    const div = document.createElement('div');
    div.textContent = texte;
    return div.innerHTML;
}
