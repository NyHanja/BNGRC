# 🤖 Système de Distribution Automatique des Dons

## 📋 Vue d'ensemble

Le **DistributionService** est un système intelligent qui distribue automatiquement les dons aux villes bénéficiaires selon leurs besoins spécifiques.

## 🎯 Fonctionnement

### Phase 1️⃣: Satisfaction des Besoins Spécifiques
Quand un don est créé:
1. Le système recherche les villes avec des **besoins non satisfaits**
2. Ces besoins doivent être du **même type** et de la **même désignation** que le don
3. Les villes sont traitées dans l'ordre de **date de saisie** (les plus anciennes en premier)
4. La quantité du don est attribuée au besoin jusqu'à satisfaction

**Exemple:**
```
Don reçu: 1000 kg de riz
Besoins non satisfaits:
  - Antananarivo: 800 kg de riz (date: 2026-02-10)
  - Toamasina: 500 kg de riz (date: 2026-02-12)

Distribution Phase 1:
  - Antananarivo reçoit 800 kg ✓ (besoin satisfait)
  - Toamasina reçoit 200 kg (reste du don)
```

### Phase 2️⃣: Distribution Équitable
S'il reste des dons après la phase 1:
1. Le système vérifie toutes les villes
2. Distribue équitablement le reste entre les villes
3. Si une distribution inégale est nécessaire, les premières villes reçoivent +1

**Exemple (suite):**
```
Reste du don après Phase 1: 0 kg (distribution complète)

Mais si le don était 1200 kg:
Reste après Phase 1: 400 kg
Villes (3 total): Antananarivo, Toamasina, Fianarantsoa
Distribution Phase 2:
  - Antananarivo: 134 kg
  - Toamasina: 133 kg
  - Fianarantsoa: 133 kg
```

## 🔧 Utilisation

### Automatique (Recommandé)
Les dons sont **automatiquement distribués** lors de la création:
```php
// Dans le formulaire de création de don
POST /dons/save
```

La distribution se fait automatiquement et le système redirige vers la liste avec le message de confirmation.

### Manuel
Vous pouvez redistribuer un don existant:
```php
// Récupérer le rapport
GET /dons/{id}/rapport

// Redistribuer (réinitialise et redistribue)
POST /dons/{id}/redistribuer
```

## 📊 Rapport de Distribution

Chaque don a un rapport détaillé accessible à:
```
GET /dons/{id}/rapport
```

Le rapport affiche:
- **Informations du don** (donateur, type, quantité)
- **Taux de distribution** (barre de progression)
- **Détails par ville** (quantité attribuée, date)

## 🔌 Architecture

### DistributionService
Fichier: `app/services/DistributionService.php`

**Méthodes principales:**

```php
// Distribuer un don
distribuerDon($idDon)

// Redistribuer un don existant
redistribuerDon($idDon)

// Obtenir le rapport
obtenirRapportDistribution($idDon)
```

### Requêtes SQL

Le service utilise des requêtes intelligentes:

1. **Besoins non satisfaits**:
```sql
SELECT b.id, b.idVille, v.nom, 
       (b.quantite - COALESCE(SUM(a.quantiteAttribuee), 0)) as quantite
FROM bngrc_besoins b
WHERE b.type = :type
  AND b.designation = :designation
  AND (b.quantite - COALESCE(SUM(a.quantiteAttribuee), 0)) > 0
ORDER BY b.dateSaisie ASC
```

## 🚀 Flux Complet

```
┌─ Utilisateur crée un don ─┐
│                           │
└──────────────────────────▶│
                            │
                    ┌───────▼────────┐
                    │ saveDon()      │
                    └───────┬────────┘
                            │
                    ┌───────▼────────┐
                    │ create()       │ (DonModel)
                    │ retourne ID    │
                    └───────┬────────┘
                            │
                    ┌───────▼────────────────┐
                    │distribuerDon(idDon)    │
                    │                        │
                    │ 1. Besoins matchants   │
                    │ 2. Distribution        │
                    │ 3. Équitable           │
                    │ 4. Commit transaction  │
                    └───────┬────────────────┘
                            │
                    ┌───────▼────────┐
                    │ Redirect       │
                    │ /dons?msg=...  │
                    └────────────────┘
                            │
                    ┌───────▼────────┐
                    │ Voir rapport   │
                    │ /dons/id/rap.. │
                    └────────────────┘
```

## 🧪 Tests

Pour tester:
1. Créer des villes
2. Créer des besoins pour diverses villes
3. Créer un don → Voir la distribution automatique
4. Consulter le rapport → Vérifier les attributions

## 📝 Notes Importantes

- **Transactions**: Tous les opérations sont en transaction pour garantir la cohérence
- **Rollback automatique**: En cas d'erreur, tout est annulé
- **Flexibilité**: Vous pouvez redistribuer un don à tout moment
- **Performance**: Utilise des LEFT JOIN pour les calculs de reste
- **Pas de quantité négative**: Le système vérifie que les restes sont positifs

## 🔄 Scénarios Avancés

### Abandon partiel
Si une ville a besoin de 500 kg mais le don n'en a que 300:
→ 300 kg attribués, 200 kg de besoin reste non satisfait

### Multiple attributions
Une même ville peut recevoir plusieurs attributions du même don:
→ Si distribution équitable dans Phase 2

### Modification du don
Modifier la quantité → `redistribuerDon()` recalcule tout

## 💾 Sauvegarde des Données

| Table | Action |
|-------|--------|
| bngrc_dons | Création du don |
| bngrc_attributions | Enregistrement de chaque attribution |
| bngrc_besoins | Lecture uniquement |
| bngrc_villes | Lecture uniquement |

---

**Exemple complet de base de données après distribution:**

```sql
-- Don créé
INSERT INTO bngrc_dons (...) VALUES (1, 'BNGRC', 'nature', 'riz', 2.50, 1000, '2026-02-16');

-- Attributions créées automatiquement
INSERT INTO bngrc_attributions (...) VALUES 
  (1, 1, 1, 'riz', 300, '2026-02-16'),  -- Antananarivo
  (2, 1, 2, 'riz', 200, '2026-02-16'),  -- Toamasina
  (3, 1, 3, 'riz', 500, '2026-02-16');  -- Fianarantsoa
```
