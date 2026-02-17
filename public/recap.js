/**
 * Recap.js - Gestion AJAX de la page récapitulatif
 */

document.addEventListener('DOMContentLoaded', () => {
    // Charger les données au démarrage
    chargerDonnees();

    // Event listeners
    document.getElementById('btnRafraichir').addEventListener('click', chargerDonnees);
    document.getElementById('btnExporter').addEventListener('click', exporterDonnees);
});

/**
 * Charger les données via AJAX
 */
function chargerDonnees() {
    const contentDiv = document.getElementById('ajax-content');
    const btnRafraichir = document.getElementById('btnRafraichir');

    // Afficher le spinner
    contentDiv.innerHTML = `
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Chargement des statistiques détaillées...</p>
        </div>
    `;

    // Désactiver le bouton
    btnRafraichir.disabled = true;

    // Requête AJAX
    fetch((window.BASE_URL || '/') + 'api/recap')
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur serveur: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                afficherResultats(data);
            } else {
                afficherErreur('Erreur lors du chargement des données');
            }
        })
        .catch(error => {
            console.error('Erreur AJAX:', error);
            afficherErreur(`Erreur: ${error.message}`);
        })
        .finally(() => {
            btnRafraichir.disabled = false;
        });
}

/**
 * Afficher les résultats reçus
 */
function afficherResultats(data) {
    const contentDiv = document.getElementById('ajax-content');
    let html = '';

    // Section Stats Globales
    const stats = data.stats;
    const statsNonSatis = stats.besoinsNonSatisfaits;
    
    html += `
        <div class="recap-section">
            <h2>📈 Statistiques Globales</h2>
            <div class="stats-global">
                <div class="stat-row">
                    <span class="stat-label">Besoins non satisfaits:</span>
                    <span class="stat-value">${statsNonSatis.total}</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Quantité totale non satisfaite:</span>
                    <span class="stat-value">${statsNonSatis.quantiteNonSatisfaite}</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Montant total non satisfait:</span>
                    <span class="stat-value">${formatMontant(statsNonSatis.montantNonSatisfait)}</span>
                </div>
            </div>
        </div>
    `;

    // Section Statistiques par Ville
    if (data.parVille && data.parVille.length > 0) {
        html += `
            <div class="recap-section">
                <h2>🏙️ Besoins non satisfaits par Ville</h2>
                <table class="recap-table">
                    <thead>
                        <tr>
                            <th>Ville</th>
                            <th class="text-center">Total Besoins</th>
                            <th class="text-center">Non Satisfaits</th>
                            <th class="text-right">Montant à Couvrir</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        data.parVille.forEach(ville => {
            html += `
                <tr>
                    <td>${echapperHTML(ville.ville)}</td>
                    <td class="text-center">${ville.nb_besoins_total}</td>
                    <td class="text-center"><strong style="color: #e74c3c;">${ville.nb_besoins_non_satisfaits}</strong></td>
                    <td class="text-right"><strong>${formatMontant(ville.montant_non_satisfait)}</strong></td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
        `;
    }

    // Section Derniers Dons
    if (data.derniersDons && data.derniersDons.length > 0) {
        html += `
            <div class="recap-section">
                <h2>🎁 Derniers Dons Enregistrés</h2>
                <div class="dons-list">
        `;

        data.derniersDons.forEach(don => {
            const date = new Date(don.dateSaisie).toLocaleDateString('fr-FR');
            html += `
                <div class="don-item">
                    <div class="don-header">
                        <span class="don-designation">${echapperHTML(don.designation)}</span>
                        <span class="don-date">${date}</span>
                    </div>
                    <div class="don-details">
                        <span class="don-quantite">Donateur: ${echapperHTML(don.donateur)}</span>
                        <span class="don-quantite">Quantité: ${don.quantite}</span>
                    </div>
                </div>
            `;
        });

        html += `
                </div>
            </div>
        `;
    }

    contentDiv.innerHTML = html;
}

/**
 * Afficher message d'erreur
 */
function afficherErreur(message) {
    const contentDiv = document.getElementById('ajax-content');
    contentDiv.innerHTML = `
        <div class="alert alert-danger">
            <strong>⚠️ Erreur</strong>
            <p>${echapperHTML(message)}</p>
        </div>
    `;
}

/**
 * Exporter les données
 */
function exporterDonnees() {
    alert('Exportation en cours de développement...');
    // À implémenter: export CSV/PDF
}

/**
 * Formater un montant en Ariary
 */
function formatMontant(montant) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'MGA',
        minimumFractionDigits: 0
    }).format(montant);
}

/**
 * Échapper les caractères HTML pour éviter les injections
 */
function echapperHTML(texte) {
    const div = document.createElement('div');
    div.textContent = texte;
    return div.innerHTML;
}