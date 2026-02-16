<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produit - E-commerce</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <a href="index.html" class="logo">E-Varotra</a>
                <ul class="menu">
                    <li><a href="index.html">Accueil</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section class="product-detail">
            <img src="images/<?php echo $produit['images'] ?>.jpg" width="300" alt="<?php echo $produit['nom'] ?>">
            <div class="info">
                <h1><?php echo $produit['nom'] ?></h1>
                <p>Description détaillée du produit.</p>
                <p><strong>Prix :</strong> <?php echo $produit['prix'] ?>Ar</p>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 E-Varotra</p>
    </footer>
</body>
</html>