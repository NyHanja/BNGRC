<?php
$nonce = Flight::get('csp_nonce');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'BNGRC - Gestion des Dons'; ?></title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="/layout.css">
</head>
<body>
    <div class="app-container">
        <!-- HEADER -->
        <header class="header">
            <div class="header-content">
                <div class="logo">
                    <h1>BNGRC</h1>
                    <p>Gestion des Dons</p>
                </div>
                <nav class="header-nav">
                    <a href="/">Accueil</a>
                    <a href="/villes">Villes</a>
                    <a href="/besoins">Besoins</a>
                    <a href="/dons">Dons</a>
                    <a href="/attributions">Attributions</a>
                    <a href="/stock-argent">Stock Argent</a>
                    <a href="/recap">Récapitulatif</a>
                    <a href="/simulation">Simulation</a>
                </nav>
                <div class="user-menu">
                    <span class="user-name">Bienvenue</span>
                    <button class="logout-btn">Déconnexion</button>
                </div>
            </div>
        </header>

        <div class="main-wrapper">
            <!-- SIDEBAR -->
            <aside class="sidebar">
                <div class="sidebar-menu">
                    <div class="menu-section">
                        <h3>Navigation</h3>
                        <ul>
                            <li><a href="/" class="menu-item">📊 Tableau de bord</a></li>
                            <li><a href="/villes" class="menu-item">🏘️ Villes</a></li>
                            <li><a href="/besoins" class="menu-item">📋 Besoins</a></li>
                            <li><a href="/dons" class="menu-item">🎁 Dons</a></li>
                            <li><a href="/attributions" class="menu-item">📦 Attributions</a></li>
                            <li><a href="/stock-argent" class="menu-item">💰 Stock Argent</a></li>
                            <li><a href="/recap" class="menu-item">📊 Récapitulatif</a></li>
                            <li><a href="/simulation" class="menu-item">🧪 Simulation</a></li>
                        </ul>
                    </div>
                    
                    <div class="menu-section">
                        <h3>Gestion</h3>
                        <ul>
                            <li><a href="/villes/create" class="menu-item">➕ Ajouter ville</a></li>
                            <li><a href="/besoins/create" class="menu-item">➕ Ajouter besoin</a></li>

                            <li><a href="/dons/create" class="menu-item">➕ Ajouter don</a></li>
                        </ul>
                    </div>

                    <div class="menu-section">
                        <h3>Rapports</h3>
                        <ul>
                            <li><a href="/rapports" class="menu-item">📈 Statistiques</a></li>
                        </ul>
                    </div>
                </div>
            </aside>

            <!-- MAIN CONTENT -->
            <main class="main-content">
                <div class="content-wrapper">
                    <?php 
                    // Inclusion du contenu dynamique
                    if(isset($viewPath) && file_exists($viewPath)) {
                        include $viewPath;
                    } else {
                        echo '<div class="alert alert-warning">Contenu non disponible</div>';
                    }
                    ?>
                </div>
            </main>
        </div>

        <!-- FOOTER -->
        <footer class="footer">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>À propos</h4>
                    <p>BNGRC - Gestion des Dons pour les régions sinistrées</p>
                </div>
                <div class="footer-section">
                    <h4>Liens utiles</h4>
                    <ul>
                        <li><a href="#">Politique de confidentialité</a></li>
                        <li><a href="#">Conditions d'utilisation</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contact</h4>
                    <p>Email: info@bngrc.mg</p>
                    <p>ETU003942--ETU004263--ETU004300</p>
                </div>
                <div class="footer-bottom">
                    <p>&copy; 2026 BNGRC. Tous droits réservés. | Créé par Harena, Nekena et NyHanja</p>
                </div>
            </div>
        </footer>
    </div>

    <script src="/layout.js" nonce="<?= $nonce ?>"></script>
</body>
</html>
