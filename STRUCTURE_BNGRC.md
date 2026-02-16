# 📋 Structure BNGRC - Gestion des Dons

## Fichiers créés

### 1. **MODÈLES** (app/models/)
- **VilleModel.php** - Modèle pour gérer les villes
  - `getAll()` - Récupérer toutes les villes
  - `getById($id)` - Récupérer une ville par ID
  - `create()` - Créer une nouvelle ville
  - `update()` - Modifier une ville
  - `delete()` - Supprimer une ville
  - `count()` - Compter les villes

- **BesoinModel.php** - Modèle pour gérer les besoins
  - `getAll()` - Récupérer tous les besoins
  - `getById($id)` - Récupérer un besoin par ID
  - `getByVille($idVille)` - Récupérer les besoins d'une ville
  - `create()` - Créer un nouveau besoin
  - `update()` - Modifier un besoin
  - `delete()` - Supprimer un besoin
  - `count()` - Compter les besoins

- **DonModel.php** - Modèle pour gérer les dons
  - `getAll()` - Récupérer tous les dons
  - `getById($id)` - Récupérer un don par ID
  - `getByType($type)` - Récupérer les dons par type
  - `create()` - Créer un nouveau don
  - `update()` - Modifier un don
  - `delete()` - Supprimer un don
  - `count()` - Compter les dons

### 2. **VUES** (app/views/)

#### Layout général
- **layout.php** - Gabarit principal avec:
  - Header (navigation principale, logo, menu utilisateur)
  - Sidebar (menu de navigation latéral)
  - Main content (zone de contenu dynamique)
  - Footer (informations, liens, copyright)

#### Vues Villes
- **villes/list.php** - Liste des villes avec CRUD
- **villes/form.php** - Formulaire ajouter/modifier ville

#### Vues Besoins
- **besoins/list.php** - Liste des besoins avec CRUD
- **besoins/form.php** - Formulaire ajouter/modifier besoin

#### Vues Dons
- **dons/list.php** - Liste des dons avec CRUD
- **dons/form.php** - Formulaire ajouter/modifier don

#### Autres
- **dashboard.php** - Tableau de bord avec statistiques

### 3. **CONTRÔLEUR** (app/controllers/)
- **LayoutController.php** - Contrôleur principal qui gère:
  - `render()` - Fonction générique pour afficher une page avec layout
  - `dashboard()` - Afficher le tableau de bord
  - Méthodes pour les listes: `listVilles()`, `listBesoins()`, `listDons()`
  - Méthodes pour créer: `createVille()`, `createBesoin()`, `createDon()`
  - Méthodes pour éditer: `editVille()`, `editBesoin()`, `editDon()`
  - Méthodes pour sauvegarder: `saveVille()`, `saveBesoin()`, `saveDon()`
  - Méthodes pour supprimer: `deleteVille()`, `deleteBesoin()`, `deleteDon()`

### 4. **CSS** (public/)
- **layout.css** - Styles complets pour:
  - Header (sticky, responsive)
  - Sidebar (menu latéral, navigation)
  - Main content (zones de contenu)
  - Tables (listes de données)
  - Formulaires (inputs, contrôles)
  - Cartes statistiques
  - Badges (pour les types)
  - Footer (pied de page)
  - Design Responsive (mobile, tablet, desktop)
  - Animations et transitions

### 5. **JAVASCRIPT** (public/)
- **layout.js** - Fonctionnalités interactives:
  - Activation du menu actif selon l'URL
  - Validation des formulaires
  - Gestion des alertes (disparition auto)
  - Smooth scrolling
  - Déconnexion confirmée
  - Utilitaires (formatage date, devise)
  - Notifications

### 6. **ROUTES** (app/config/routes.php)
Routes ajoutées pour:
- `/` - Tableau de bord
- `/villes` - Liste des villes
- `/villes/create` - Créer ville
- `/villes/:id/edit` - Éditer ville
- `/villes/save` - Sauvegarder ville
- `/villes/:id/delete` - Supprimer ville
- `/besoins` - Liste des besoins
- `/besoins/create` - Créer besoin
- `/besoins/:id/edit` - Éditer besoin
- `/besoins/save` - Sauvegarder besoin
- `/besoins/:id/delete` - Supprimer besoin
- `/dons` - Liste des dons
- `/dons/create` - Créer don
- `/dons/:id/edit` - Éditer don
- `/dons/save` - Sauvegarder don
- `/dons/:id/delete` - Supprimer don

## Architecture MVC

```
BNGRC/
├── app/
│   ├── controllers/
│   │   ├── ApiExampleController.php
│   │   └── LayoutController.php ✨ (NOUVEAU)
│   ├── models/
│   │   ├── ProduitModel.php
│   │   ├── VilleModel.php ✨ (NOUVEAU)
│   │   ├── BesoinModel.php ✨ (NOUVEAU)
│   │   └── DonModel.php ✨ (NOUVEAU)
│   ├── views/
│   │   ├── layout.php ✨ (NOUVEAU)
│   │   ├── dashboard.php ✨ (NOUVEAU)
│   │   ├── villes/ ✨ (NOUVEAU)
│   │   │   ├── list.php
│   │   │   └── form.php
│   │   ├── besoins/ ✨ (NOUVEAU)
│   │   │   ├── list.php
│   │   │   └── form.php
│   │   ├── dons/ ✨ (NOUVEAU)
│   │   │   ├── list.php
│   │   │   └── form.php
│   │   └── ...
│   └── config/
│       └── routes.php (MODIFIÉ)
└── public/
    ├── layout.css ✨ (NOUVEAU)
    ├── layout.js ✨ (NOUVEAU)
    └── ...
```

## Fonctionnalités

✅ **Modèles CRUD complets** pour chaque table  
✅ **Layout responsive** avec header, sidebar, footer  
✅ **Contrôleur centralisé** pour tous les affichages  
✅ **Vues dynamiques** pour chaque section  
✅ **CSS moderne** avec animations et responsive design  
✅ **JavaScript** pour les interactions et validations  
✅ **Routes complètes** intégrées à Flight PHP  
✅ **Gestion des erreurs** et confirmations  
✅ **Design UI/UX** professionnel  

## Comment utiliser

1. **Accéder au tableau de bord:** `http://localhost/`
2. **Naviguer via le menu** sidebar ou header
3. **Créer, modifier, supprimer** des données via les formulaires
4. **Voir les statistiques** sur le dashboard

## Prochaines étapes (optionnel)

- [ ] Ajouter l'authentification
- [ ] Ajouter les validations côté serveur
- [ ] Créer des rapports/exports
- [ ] Ajouter les attributions (table commentée dans la DB)
- [ ] Ajouter des graphiques aux statistiques
- [ ] Implémenter des recherches/filtres avancés
