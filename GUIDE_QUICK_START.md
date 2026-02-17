# 🚀 GUIDE RAPIDE - BNGRC CRUD

Bienvenue! Voici la structure complète créée pour votre application BNGRC.

## ✅ Ce qui a été créé

### 3 Modèles complets avec CRUD:
- **VilleModel** - Gestion des villes
- **BesoinModel** - Gestion des besoins
- **DonModel** - Gestion des dons

### 1 Layout principal avec:
- Header responsive (logo + navigation)
- Sidebar avec menu latéral
- Footer avec informations
- Zone de contenu dynamique

### 1 Contrôleur centralisé (LayoutController) qui gère:
- Récupération et affichage des données
- Inclusion dynamique des vues
- CRUD complet pour chaque table

### Vues organisées:
- Dashboard avec statistiques
- Listes pour villes, besoins, dons
- Formulaires create/edit pour chaque entité

### CSS & JS:
- Design moderne et responsive
- Animations et transitions
- Validations de formulaires
- Menu actif dynamique

## 📁 Structure des fichiers créés

```
✨ NEW FILES:
✨ app/models/VilleModel.php
✨ app/models/BesoinModel.php
✨ app/models/DonModel.php
✨ app/controllers/LayoutController.php
✨ app/views/layout.php
✨ app/views/dashboard.php
✨ app/views/villes/list.php
✨ app/views/villes/form.php
✨ app/views/besoins/list.php
✨ app/views/besoins/form.php
✨ app/views/dons/list.php
✨ app/views/dons/form.php
✨ public/layout.css
✨ public/layout.js

📝 MODIFIED:
📝 app/config/routes.php
```

## 🔌 Configuration requise

### 1. Vérifier la connexion à la base de données

Assurez-vous que `Flight::db()` est disponible dans votre bootstrap.php:

```php
// app/config/bootstrap.php
use Flight;
use PDO;

// Connexion à la base de données
try {
    $db = new PDO(
        'mysql:host=localhost;dbname=bngrc;charset=utf8mb4',
        'root',  // votre utilisateur
        '',      // votre mot de passe
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    
    Flight::set('db', $db);
    Flight::register('db', PDO::class, [$db]);
} catch(Exception $e) {
    die('Erreur DB: ' . $e->getMessage());
}
```

### 2. Vérifier que les routes sont chargées

Le fichier `app/config/routes.php` a été modifié avec toutes les routes nécessaires.

## 🌐 URLs disponibles

### Tableau de bord
- `GET /` - Dashboard avec statistiques

### Villes
- `GET /villes` - Lister les villes
- `GET /villes/create` - Formulaire új ville
- `GET /villes/:id/edit` - Formulaire édition
- `POST /villes/save` - Sauvegarder
- `POST /villes/:id/delete` - Supprimer

### Besoins
- `GET /besoins` - Lister les besoins
- `GET /besoins/create` - Formulaire új
- `GET /besoins/:id/edit` - Formulaire édition
- `POST /besoins/save` - Sauvegarder
- `POST /besoins/:id/delete` - Supprimer

### Dons
- `GET /dons` - Lister les dons
- `GET /dons/create` - Formulaire új
- `GET /dons/:id/edit` - Formulaire édition
- `POST /dons/save` - Sauvegarder
- `POST /dons/:id/delete` - Supprimer

## 🎨 Personnalisation

### Couleurs
Modifier les variables CSS dans `public/layout.css`:
```css
:root {
    --primary-color: #2c3e50;      /* Couleur principale */
    --secondary-color: #3498db;    /* Couleur secondaire */
    --danger-color: #e74c3c;       /* Couleur danger */
    --success-color: #27ae60;      /* Couleur succès */
}
```

### Logo
Modifier le logo dans `app/views/layout.php`:
```php
<div class="logo">
    <h1>BNGRC</h1>
    <p>Gestion des Dons</p>
</div>
```

### Menu
Ajouter/modifier les liens dans le header et sidebar:
```php
<!-- Header nav -->
<a href="...">Nouveau lien</a>

<!-- Sidebar -->
<li><a href="..." class="menu-item">📦 Nouveau</a></li>
```

## ✨ Caractéristiques

- **Design Responsive** - Fonctionne sur desktop, tablet, mobile
- **CRUD Complet** - Créer, lire, mettre à jour, supprimer
- **Layout Persistent** - Header/sidebar/footer sur chaque page
- **Validation** - Validations côté client et prêt pour serveur
- **Statistiques** - Cartes avec totaux au dashboard
- **Gestion d'erreurs** - Confirmations de suppression
- **UX Moderne** - Animations, transitions, design épuré

## 🐛 Dépannage

### Erreur: "Class not found"
Vérifier que le namespace `app\models\` et `app\controllers\` correspondent à votre structure

### Erreur: "Database connection"
Vérifier que `Flight::db()` est défini dans bootstrap.php

### Les routes ne marchent pas
Vérifier que `app/config/routes.php` est inclus dans `app/config/bootstrap.php`

### Styles/JS ne chargent pas
Vérifier que `Flight::get('flight.base_url')` est bien configuré (par défaut `/`)

## 📚 Structure MVC expliquée

```
MODÈLE (Model)
├─ Récupère les données de la DB
├─ Validations métier
└─ Retourne les données

   ↓

CONTRÔLEUR (Controller)
├─ Reçoit la requête
├─ Appelle le modèle
├─ Prépare les données
└─ Appelle la vue

   ↓

VUE (View)
├─ Affiche les données
├─ Formulaires
└─ Interaction utilisateur
```

## 🎯 Prochaines étapes recommandées

1. Tester les CRUD sur chaque table
2. Ajouter des validations côté serveur
3. Implémenter l'authentification
4. Ajouter les recherches/filtres
5. Créer des exports (CSV, PDF)
6. Ajouter des graphiques au dashboard

---

**Questions?** Consultez la documentation Flow PHP: http://flightphp.com/
