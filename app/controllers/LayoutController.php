<?php
namespace app\controllers;
use app\models\VilleModel;
use app\models\BesoinModel;
use app\models\DonModel;
use app\models\AttributionModel;
use app\models\StockArgentModel;
use app\models\RepartitionArgentModel;
use app\services\DistributionService;
use Flight;

class LayoutController {
    private $villeModel;
    private $besoinModel;
    private $donModel;
    private $attributionModel;
    private $stockArgentModel;
    private $repartitionArgentModel;
    private $distributionService;

    public function __construct() {
        // Récupérer la base de données depuis Flight
        $this->villeModel = new VilleModel(Flight::db());
        $this->besoinModel = new BesoinModel(Flight::db());
        $this->donModel = new DonModel(Flight::db());
        $this->attributionModel = new AttributionModel(Flight::db());
        $this->stockArgentModel = new StockArgentModel(Flight::db());
        $this->repartitionArgentModel = new RepartitionArgentModel(Flight::db());
        $this->distributionService = new DistributionService(Flight::db());
    }

    /**
     * Fonction générique pour afficher une page avec layout
     */
    public function render($view, $data = []) {
        // Extraire les données pour qu'elles soient disponibles dans la vue
        extract($data);

        // Définir le chemin de la vue
        $viewPath = __DIR__ . '/../views/' . $view . '.php';
        
        // Passer les informations au layout
        $pageTitle = isset($data['pageTitle']) ? $data['pageTitle'] : 'BNGRC';

        // Afficher le layout
        include __DIR__ . '/../views/layout.php';
    }

    /**
     * Dashboard / Accueil
     */
    public function dashboard() {
        $data = [
            'pageTitle' => 'Tableau de bord',
            'totalVilles' => $this->villeModel->count(),
            'totalBesoins' => $this->besoinModel->count(),
            'totalDons' => $this->donModel->count(),
            'totalAttributions' => $this->attributionModel->count(),
            'villes' => $this->villeModel->getAll(),
            'dons' => array_slice($this->donModel->getAll(), 0, 5),
            'besoins' => array_slice($this->besoinModel->getAll(), 0, 5),
            'attributions' => array_slice($this->attributionModel->getAll(), 0, 5)
        ];

        $this->render('dashboard', $data);
    }

    /**
     * Liste des villes
     */
    public function listVilles() {
        $data = [
            'pageTitle' => 'Gestion des Villes',
            'villes' => $this->villeModel->getAll()
        ];

        $this->render('villes/list', $data);
    }

    /**
     * Créer une ville
     */
    public function createVille() {
        $data = [
            'pageTitle' => 'Ajouter une ville'
        ];

        $this->render('villes/form', $data);
    }

    /**
     * Éditer une ville
     */
    public function editVille($id) {
        $data = [
            'pageTitle' => 'Modifier une ville',
            'ville' => $this->villeModel->getById($id)
        ];

        $this->render('villes/form', $data);
    }

    /**
     * Liste des besoins
     */
    public function listBesoins() {
        $data = [
            'pageTitle' => 'Gestion des Besoins',
            'besoins' => $this->besoinModel->getAll(),
            'villes' => $this->villeModel->getAll()
        ];

        $this->render('besoins/list', $data);
    }

    /**
     * Créer un besoin
     */
    public function createBesoin() {
        $data = [
            'pageTitle' => 'Ajouter un besoin',
            'villes' => $this->villeModel->getAll()
        ];

        $this->render('besoins/form', $data);
    }

    /**
     * Éditer un besoin
     */
    public function editBesoin($id) {
        $data = [
            'pageTitle' => 'Modifier un besoin',
            'besoin' => $this->besoinModel->getById($id),
            'villes' => $this->villeModel->getAll()
        ];

        $this->render('besoins/form', $data);
    }

    /**
     * Liste des dons
     */
    public function listDons() {
        $data = [
            'pageTitle' => 'Gestion des Dons',
            'dons' => $this->donModel->getAll()
        ];

        $this->render('dons/list', $data);
    }

    /**
     * Créer un don
     */
    public function createDon() {
        $data = [
            'pageTitle' => 'Ajouter un don',
            'villes' => $this->villeModel->getAll()
        ];

        $this->render('dons/form', $data);
    }

    /**
     * Éditer un don
     */
    public function editDon($id) {
        $data = [
            'pageTitle' => 'Modifier un don',
            'don' => $this->donModel->getById($id),
            'villes' => $this->villeModel->getAll()
        ];

        $this->render('dons/form', $data);
    }

    /**
     * Traiter les soumissions de formulaire (create/update)
     */
    public function saveVille() {
        $id = Flight::request()->data->id;
        $nom = Flight::request()->data->nom;
        $region = Flight::request()->data->region;

        if($id) {
            $this->villeModel->update($id, $nom, $region);
            Flight::redirect('/villes?msg=updated');
        } else {
            $this->villeModel->create($nom, $region);
            Flight::redirect('/villes?msg=created');
        }
    }

    public function saveBesoin() {
        $id = Flight::request()->data->id;
        $idVille = Flight::request()->data->idVille;
        $type = Flight::request()->data->type;
        $designation = Flight::request()->data->designation;
        $prixUnitaire = Flight::request()->data->prixUnitaire;
        $quantite = Flight::request()->data->quantite;
        $dateSaisie = date('Y-m-d');

        if($id) {
            $this->besoinModel->update($id, $idVille, $type, $designation, $prixUnitaire, $quantite, $dateSaisie);
            Flight::redirect('/besoins?msg=updated');
        } else {
            $this->besoinModel->create($idVille, $type, $designation, $prixUnitaire, $quantite, $dateSaisie);
            Flight::redirect('/besoins?msg=created');
        }
    }

    public function saveDon() {
        $id = Flight::request()->data->id;
        $donateur = Flight::request()->data->donateur;
        $type = Flight::request()->data->type;
        $designation = Flight::request()->data->designation;
        $montantUnitaire = Flight::request()->data->montantUnitaire;
        $quantite = Flight::request()->data->quantite;
        $dateSaisie = date('Y-m-d');
        $idVilleDestinaire = Flight::request()->data->idVilleDestinaire ?? null;

        // Pour les dons d'argent, forcer quantite=1 (montantUnitaire = montant total)
        if ($type === 'argent') {
            $quantite = 1;
        }

        if($id) {
            // Mise à jour d'un don existant
            $this->donModel->update($id, $donateur, $type, $designation, $montantUnitaire, $quantite, $dateSaisie);
            
            // Redistribuer automatiquement
            $resultat = $this->distributionService->redistribuerDon($id);
            
            if ($resultat['success']) {
                Flight::redirect('/dons?msg=updated&distributed=1');
            } else {
                Flight::redirect('/dons?msg=error');
            }
        } else {
            // Créer un nouveau don (sans idVille, sera spécifiée lors de l'attribution)
            $lastDonId = $this->donModel->create($donateur, $type, $designation, $montantUnitaire, $quantite, $dateSaisie);
            
            if ($lastDonId) {
                // Distribuer automatiquement le don
                $resultat = $this->distributionService->distribuerDon($lastDonId);
                
                if ($resultat['success']) {
                    Flight::redirect('/dons?msg=created&distributed=1');
                } else {
                    Flight::redirect('/dons?msg=created');
                }
            } else {
                Flight::redirect('/dons?msg=error');
            }
        }
    }

    /**
     * Supprimer des enregistrements
     */
    public function deleteVille($id) {
        $this->villeModel->delete($id);
        Flight::redirect('/villes?msg=deleted');
    }

    public function deleteBesoin($id) {
        $this->besoinModel->delete($id);
        Flight::redirect('/besoins?msg=deleted');
    }

    public function deleteDon($id) {
        $this->donModel->delete($id);
        Flight::redirect('/dons?msg=deleted');
    }

    /**
     * Liste des attributions
     */
    public function listAttributions() {
        $data = [
            'pageTitle' => 'Gestion des Attributions',
            'attributions' => $this->attributionModel->getAll()
        ];

        $this->render('attributions/list', $data);
    }

    /**
     * Créer une attribution
     */
    public function createAttribution() {
        $data = [
            'pageTitle' => 'Ajouter une attribution',
            'dons' => $this->donModel->getAll(),
            'villes' => $this->villeModel->getAll()
        ];

        $this->render('attributions/form', $data);
    }

    /**
     * Éditer une attribution
     */
    public function editAttribution($id) {
        $data = [
            'pageTitle' => 'Modifier une attribution',
            'attribution' => $this->attributionModel->getById($id),
            'dons' => $this->donModel->getAll(),
            'villes' => $this->villeModel->getAll()
        ];

        $this->render('attributions/form', $data);
    }

    /**
     * Sauvegarder une attribution
     */
    public function saveAttribution() {
        $id = Flight::request()->data->id;
        $idDons = Flight::request()->data->idDons;
        $idVille = Flight::request()->data->idVille;
        $designation = Flight::request()->data->designation;
        $quantiteAttribuee = Flight::request()->data->quantiteAttribuee;
        $dateAttribution = Flight::request()->data->dateAttribution;

        if($id) {
            $this->attributionModel->update($id, $idDons, $idVille, $designation, $quantiteAttribuee, $dateAttribution);
            Flight::redirect('/attributions?msg=updated');
        } else {
            $this->attributionModel->create($idDons, $idVille, $designation, $quantiteAttribuee, $dateAttribution);
            Flight::redirect('/attributions?msg=created');
        }
    }

    /**
     * Supprimer une attribution
     */
    public function deleteAttribution($id) {
        $this->attributionModel->delete($id);
        Flight::redirect('/attributions?msg=deleted');
    }

    /**
     * Afficher le rapport de distribution d'un don
     */
    public function rapportDistribution($idDon) {
        $rapport = $this->distributionService->obtenirRapportDistribution($idDon);
        
        if (!$rapport['don']) {
            Flight::notFound();
            return;
        }

        $data = [
            'pageTitle' => 'Rapport de Distribution - ' . $rapport['don']['donateur'],
            'rapport' => $rapport
        ];

        $this->render('rapports/distribution', $data);
    }

    /**
     * Redistribuer un don
     */
    public function redistributre($idDon) {
        $resultat = $this->distributionService->redistribuerDon($idDon);
        
        if ($resultat['success']) {
            Flight::redirect('/dons/' . $idDon . '/rapport?msg=redistributed');
        } else {
            Flight::redirect('/dons/' . $idDon . '/rapport?msg=error');
        }
    }
    public function recap() {
        $besoinsNonSatisfaits = $this->attributionModel->getRecap();
        
        $data = [
            'pageTitle' => 'Récapitulatif - Besoins non satisfaits',
            'totalVilles' => $this->villeModel->count(),
            'totalBesoins' => $this->besoinModel->count(),
            'totalDons' => $this->donModel->count(),
            'totalAttributions' => $this->attributionModel->count(),
            'besoinsNonSatisfaits' => $besoinsNonSatisfaits
        ];
        $this->render('recap', $data);
    }

    /**
     * API pour récupérer le récapitulatif en JSON
     */
    public function recapApi() {
        $villes = $this->villeModel->getAll();
        $besoins = $this->besoinModel->getAll();
        $dons = $this->donModel->getAll();
        $attributions = $this->attributionModel->getAll();
        
        // Récupérer los besoins non satisfaits
        $besoinsNonSatisfaits = $this->attributionModel->getRecap();
        
        // Statistiques globales sur besoins non satisfaits
        $statsNonSatisfaits = [
            'total' => count($besoinsNonSatisfaits),
            'montantNonSatisfait' => 0,
            'quantiteNonSatisfaite' => 0,
        ];
        
        foreach ($besoinsNonSatisfaits as $besoin) {
            $statsNonSatisfaits['quantiteNonSatisfaite'] += $besoin['quantiteNonSatisfaite'];
            $statsNonSatisfaits['montantNonSatisfait'] += $besoin['MontantBesoinNonSatisfait'];
        }
        
        // Statistiques par ville (besoins satisfaits vs non satisfaits)
        $statsByVille = [];
        foreach ($villes as $ville) {
            $villeId = $ville['id'];
            $besoinsByVille = array_filter($besoins, fn($b) => $b['idVille'] == $villeId);
            $besoinNonSatisfaitByVille = array_filter($besoinsNonSatisfaits, fn($b) => strpos($b['nomVille'], $ville['nom']) !== false);
            
            $statsByVille[] = [
                'ville' => $ville['nom'],
                'nb_besoins_total' => count($besoinsByVille),
                'nb_besoins_non_satisfaits' => count($besoinNonSatisfaitByVille),
                'montant_non_satisfait' => array_sum(array_column($besoinNonSatisfaitByVille, 'MontantBesoinNonSatisfait'))
            ];
        }
        
        Flight::json([
            'success' => true,
            'stats' => [
                'totalVilles' => count($villes),
                'totalBesoins' => count($besoins),
                'totalDons' => count($dons),
                'totalAttributions' => count($attributions),
                'besoinsNonSatisfaits' => $statsNonSatisfaits,
            ],
            'parVille' => $statsByVille,
            'derniersDons' => array_slice($dons, -5),
            'besoinsNonSatisfaites' => $besoinsNonSatisfaits,
        ]);
    }

    /**
     * Page de simulation
     */
    public function simulation() {
        $besoinsNonSatisfaits = $this->attributionModel->getRecap();
        
        $data = [
            'pageTitle' => 'Simulation de Distribution',
            'besoinsNonSatisfaits' => $besoinsNonSatisfaits,
            'dons' => $this->donModel->getAll()
        ];
        $this->render('simulation', $data);
    }

    /**
     * API: Simuler la distribution (sans modification)
     */
    public function simulationApi() {
        // Récupérer les paramètres
        $besoinId = Flight::request()->query['besoinId'];
        $quantiteVoulue = Flight::request()->query['quantite'] ?? 1;
        $tauxFrais = Flight::request()->query['frais'] ?? 0.05; // 5% par défaut

        $besoinsNonSatisfaits = $this->attributionModel->getRecap();

        // Chercher le besoin dans les besoins non satisfaits
        $besoinCible = null;
        foreach ($besoinsNonSatisfaits as $b) {
            if ($b['idBesoin'] == $besoinId) {
                $besoinCible = $b;
                break;
            }
        }

        if (!$besoinCible) {
            Flight::json([
                'success' => false,
                'message' => 'Besoin non trouvé'
            ]);
            return;
        }

        // Calculs de simulation
        $quantiteDisponible = $besoinCible['quantiteNonSatisfaite'];
        $quantiteAAttribuer = min($quantiteVoulue, $quantiteDisponible);
        
        // Montant calculation
        $montantUnitaire = $besoinCible['MontantBesoin'] / $besoinCible['quantiteBesoin'];
        $montantBrut = $montantUnitaire * $quantiteAAttribuer;
        $frais = $montantBrut * $tauxFrais;
        $montantNet = $montantBrut + $frais;

        // Vérifier le stockArgent de la ville du besoin
        $idVille = $besoinCible['idVille'];
        $stock = $this->stockArgentModel->getByVille($idVille);
        $stockDisponible = $stock ? (int)$stock['quantite'] : 0;
        $stockSuffisant = $stockDisponible >= $montantNet;
        $sourceFinancement = 'stockArgent';

        // Déterminer l'impact
        $quantiteRestante = $quantiteDisponible - $quantiteAAttribuer;
        $montantRestant = $besoinCible['MontantBesoinNonSatisfait'] - $montantBrut;

        Flight::json([
            'success' => true,
            'besoin' => $besoinCible,
            'simulation' => [
                'quantiteVoulue' => $quantiteVoulue,
                'quantiteDisponible' => $quantiteDisponible,
                'quantiteAAttribuer' => $quantiteAAttribuer,
                'montantUnitaire' => $montantUnitaire,
                'montantBrut' => $montantBrut,
                'tauxFrais' => $tauxFrais * 100,
                'frais' => $frais,
                'montantNet' => $montantNet,
                'quantiteRestante' => $quantiteRestante,
                'montantRestant' => $montantRestant,
                'sourceFinancement' => $sourceFinancement,
                'stockDisponible' => $stockDisponible,
                'stockSuffisant' => $stockSuffisant,
                'villeNom' => $besoinCible['nomVille']
            ]
        ]);
    }

    /**
     * API: Valider et enregistrer la distribution
     */
    public function validerSimulation() {
        $data = Flight::request()->data;
        
        $besoinId = $data->besoinId;
        $quantiteAAttribuer = $data->quantite;
        $montantNet = $data->montantNet ?? 0;
        
        try {
            $besoinsNonSatisfaits = $this->attributionModel->getRecap();
            
            // Trouve le besoin
            $besoincible = null;
            foreach ($besoinsNonSatisfaits as $b) {
                if ($b['idBesoin'] == $besoinId) {
                    $besoincible = $b;
                    break;
                }
            }
            
            if (!$besoincible) {
                throw new \Exception('Besoin non trouvé');
            }
            
            $villeId = $besoincible['idVille'];
            if (!$villeId) {
                throw new \Exception('Ville non spécifiée');
            }
            
            // Déduire du stockArgent de la ville
            if ($montantNet > 0) {
                // Vérifier que le stock est suffisant
                if (!$this->stockArgentModel->verifierStock($villeId, $montantNet)) {
                    $stockActuel = $this->stockArgentModel->getByVille($villeId);
                    $disponible = $stockActuel ? $stockActuel['quantite'] : 0;
                    throw new \Exception('Stock d\'argent insuffisant pour ' . $besoincible['nomVille'] . '. Disponible: ' . number_format($disponible, 0, ',', ' ') . ' Ar, Nécessaire: ' . number_format($montantNet, 0, ',', ' ') . ' Ar');
                }
                $this->stockArgentModel->deduire($villeId, $montantNet);
            }
            
            // Créer l'attribution
            $this->attributionModel->create(
                $besoincible['idBesoin'],
                $villeId,
                $besoincible['designationBesoin'],
                $quantiteAAttribuer,
                date('Y-m-d')
            );
            
            Flight::json([
                'success' => true,
                'message' => 'Attribution enregistrée avec succès. ' . ($besoincible['typeBesoin'] === 'argent' ? number_format($montantNet, 0, ',', ' ') . ' Ar déduits du stock.' : ''),
                'quantiteAttribuee' => $quantiteAAttribuer
            ]);
            
        } catch (\Exception $e) {
            Flight::json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Lister les stocks d'argent par ville
     */
    public function listStockArgent() {
        $data = [
            'pageTitle' => 'Suivi du Stock d\'Argent',
            'stocks' => $this->stockArgentModel->getAll()
        ];
        $this->render('stock-argent', $data);
    }

    /**
     * Redistribuer tous les dons d'argent existants dans stockArgent
     */
    public function redistribuerTousDonsArgent() {
        $resultat = $this->distributionService->redistribuerTousDonsArgent();
        
        if ($resultat['success']) {
            Flight::redirect('/stock-argent?msg=redistributed');
        } else {
            Flight::redirect('/stock-argent?msg=error&detail=' . urlencode($resultat['message']));
        }
    }

    /**
     * Lister l'historique des répartitions d'argent
     */
    public function repartitionHistorique() {
        $data = [
            'pageTitle' => 'Historique de Répartition d\'Argent',
            'historique' => $this->repartitionArgentModel->getHistorique()
        ];
        $this->render('repartition-historique', $data);
    }

    /**
     * Vérifier si un besoin est complètement satisfait
     * Si oui et c'est un besoin d'argent, répartir les argents restants entre les villes
     */
    private function verifierEtRepartirArgentSiComplet($idBesoin, $typeBesoin, $quantiteRequise) {
        try {
            // Récupérer les attributions pour ce besoin
            $stmt = Flight::db()->prepare("
                SELECT SUM(quantiteAttribuee) as quantiteTotale
                FROM bngrc_attributions
                WHERE idBesoin = ?
            ");
            $stmt->execute([$idBesoin]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            $quantiteAttribuee = $result['quantiteTotale'] ?? 0;
            
            // Si besoin d'argent et complètement satisfait, répartir les argents excédentaires
            if ($typeBesoin === 'argent' && $quantiteAttribuee >= $quantiteRequise) {
                $argentExcedent = $quantiteAttribuee - $quantiteRequise;
                
                if ($argentExcedent > 0) {
                    // Répartir l'argent excédentaire entre toutes les villes
                    $this->repartitionArgentModel->repartirParVilles($idBesoin, $argentExcedent);
                }
            }
        } catch (\Exception $e) {
            // Logger l'erreur mais ne pas bloquer le processus
            error_log("Erreur repartition argent: " . $e->getMessage());
        }
    }
}
?>
