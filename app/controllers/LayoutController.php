<?php
namespace app\controllers;
use app\models\VilleModel;
use app\models\BesoinModel;
use app\models\DonModel;
use app\models\AttributionModel;
use app\services\DistributionService;
use Flight;

class LayoutController {
    private $villeModel;
    private $besoinModel;
    private $donModel;
    private $attributionModel;
    private $distributionService;

    public function __construct() {
        // Récupérer la base de données depuis Flight
        $this->villeModel = new VilleModel(Flight::db());
        $this->besoinModel = new BesoinModel(Flight::db());
        $this->donModel = new DonModel(Flight::db());
        $this->attributionModel = new AttributionModel(Flight::db());
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
            'pageTitle' => 'Ajouter un don'
        ];

        $this->render('dons/form', $data);
    }

    /**
     * Éditer un don
     */
    public function editDon($id) {
        $data = [
            'pageTitle' => 'Modifier un don',
            'don' => $this->donModel->getById($id)
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
            // Créer un nouveau don
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
}
?>
