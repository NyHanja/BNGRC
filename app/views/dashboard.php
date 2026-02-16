<div class="dashboard">
    <h2>Tableau de bord</h2>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">🏘️</div>
            <div class="stat-info">
                <p class="stat-label">Total Villes</p>
                <p class="stat-value"><?php echo $totalVilles; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div class="stat-info">
                <p class="stat-label">Total Besoins</p>
                <p class="stat-value"><?php echo $totalBesoins; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🎁</div>
            <div class="stat-info">
                <p class="stat-label">Total Dons</p>
                <p class="stat-value"><?php echo $totalDons; ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-info">
                <p class="stat-label">Total Attributions</p>
                <p class="stat-value"><?php echo $totalAttributions; ?></p>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="dashboard-section">
            <h3>Derniers Dons</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Donateur</th>
                        <th>Type</th>
                        <th>Désignation</th>
                        <th>Quantité</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($dons as $don): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($don['donateur']); ?></td>
                        <td><span class="badge badge-<?php echo $don['type']; ?>"><?php echo ucfirst($don['type']); ?></span></td>
                        <td><?php echo htmlspecialchars($don['designation']); ?></td>
                        <td><?php echo $don['quantite']; ?></td>
                        <td><?php echo $don['dateSaisie']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="dashboard-section">
            <h3>Derniers Besoins</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ville</th>
                        <th>Type</th>
                        <th>Désignation</th>
                        <th>Quantité</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($besoins as $besoin): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($besoin['ville'] ?? 'N/A'); ?></td>
                        <td><span class="badge badge-<?php echo $besoin['type']; ?>"><?php echo ucfirst($besoin['type']); ?></span></td>
                        <td><?php echo htmlspecialchars($besoin['designation']); ?></td>
                        <td><?php echo $besoin['quantite']; ?></td>
                        <td><?php echo $besoin['dateSaisie']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="dashboard-section">
            <h3>Dernières Attributions</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Donateur</th>
                        <th>Ville</th>
                        <th>Désignation</th>
                        <th>Quantité</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($attributions as $attribution): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($attribution['donateur'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($attribution['ville'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($attribution['designation']); ?></td>
                        <td><?php echo $attribution['quantiteAttribuee']; ?></td>
                        <td><?php echo $attribution['dateAttribution']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
