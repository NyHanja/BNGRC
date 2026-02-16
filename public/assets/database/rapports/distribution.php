<div class="page-section">
    <div class="page-header">
        <h2>Rapport de Distribution - <?php echo htmlspecialchars($rapport['don']['donateur']); ?></h2>
        <a href="dons" class="btn btn-secondary">← Retour</a>
    </div>

    <div class="rapport-container">
        <div class="info-box">
            <h3>Informations du Don</h3>
            <table class="info-table">
                <tr>
                    <th>Donateur</th>
                    <td><?php echo htmlspecialchars($rapport['don']['donateur']); ?></td>
                </tr>
                <tr>
                    <th>Type</th>
                    <td><span class="badge badge-<?php echo $rapport['don']['type']; ?>"><?php echo ucfirst($rapport['don']['type']); ?></span></td>
                </tr>
                <tr>
                    <th>Désignation</th>
                    <td><?php echo htmlspecialchars($rapport['don']['designation']); ?></td>
                </tr>
                <tr>
                    <th>Quantité Totale</th>
                    <td><strong><?php echo $rapport['don']['quantite']; ?></strong></td>
                </tr>
                <tr>
                    <th>Quantité Distribuée</th>
                    <td><strong><?php echo $rapport['totalDistribue']; ?></strong></td>
                </tr>
                <tr>
                    <th>Reste</th>
                    <td><strong><?php echo $rapport['reste']; ?></strong></td>
                </tr>
                <tr>
                    <th>Taux de Distribution</th>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $rapport['pourcentageDistribue']; ?>%"></div>
                            <span><?php echo round($rapport['pourcentageDistribue'], 1); ?>%</span>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="info-box">
            <h3>Détails de Distribution</h3>
            <?php if (!empty($rapport['attributions'])): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ville</th>
                        <th>Quantité Attribuée</th>
                        <th>Date Attribution</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rapport['attributions'] as $attribution): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($attribution['ville'] ?? 'N/A'); ?></td>
                        <td><?php echo $attribution['quantiteAttribuee']; ?></td>
                        <td><?php echo $attribution['dateAttribution']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="alert alert-info">Aucune attribution enregistrée pour ce don.</p>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .rapport-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .info-box {
            background-color: #f9f9f9;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .info-box h3 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 0.5rem;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table tr {
            border-bottom: 1px solid #e0e0e0;
        }

        .info-table tr:hover {
            background-color: #f0f0f0;
        }

        .info-table th {
            text-align: left;
            padding: 0.8rem;
            background-color: #f0f0f0;
            font-weight: 600;
            color: #2c3e50;
            width: 40%;
        }

        .info-table td {
            padding: 0.8rem;
        }

        .progress-bar {
            position: relative;
            height: 25px;
            background-color: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .progress-fill {
            position: absolute;
            height: 100%;
            background: linear-gradient(90deg, #27ae60, #2ecc71);
            transition: width 0.3s ease;
            left: 0;
            top: 0;
        }

        .progress-bar span {
            position: relative;
            z-index: 1;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
        }
    </style>
</div>
