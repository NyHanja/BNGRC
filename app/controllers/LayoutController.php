<?php
namespace app\controllers;
use app\models\VilleModel;
use app\models\BesoinModel;
use app\models\DonModel;
use Flight;

class LayoutController {
    private $villeModel;
    private $besoinModel;
    private $donModel;

    public function __construct() {
        // Récupérer la base de données depuis Flight
        $this->villeModel = new VilleModel(Flight::db());
        $this->besoinModel = new BesoinModel(Flight::db());
        $this->donModel = new DonModel(Flight::db());
    }

    /**
     * Fonction générique pour afficher une page avec layout
     */
    public function render($view, $data = []) {
        // Passer les données à la vue
        foreach($data as $key => $value) {
            Flight::set($key, $value);
        }

        // Définir le chemin de la vue
        $viewPath = __DIR__ . '/../views/' . $view . '.php';
        
        // Passer les informations au layout
        Flight::set('view', $viewPath);
        Flight::set('pageTitle', isset($data['pageTitle']) ? $data['pageTitle'] : 'BNGRC');

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
            'villes' => $this->villeModel->getAll(),
            'dons' => array_slice($this->donModel->getAll(), 0, 5),
            'besoins' => array_slice($this->besoinModel->getAll(), 0, 5)
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
        $id = Flight::request()->data('id');
        $nom = Flight::request()->data('nom');
        $region = Flight::request()->data('region');

        if($id) {
            $this->villeModel->update($id, $nom, $region);
            Flight::redirect('/villes?msg=updated');
        } else {
            $this->villeModel->create($nom, $region);
            Flight::redirect('/villes?msg=created');
        }
    }

    public function saveBesoin() {
        $id = Flight::request()->data('id');
        $idVille = Flight::request()->data('idVille');
        $type = Flight::request()->data('type');
        $designation = Flight::request()->data('designation');
        $prixUnitaire = Flight::request()->data('prixUnitaire');
        $quantite = Flight::request()->data('quantite');
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
        $id = Flight::request()->data('id');
        $donateur = Flight::request()->data('donateur');
        $type = Flight::request()->data('type');
        $designation = Flight::request()->data('designation');
        $montantUnitaire = Flight::request()->data('montantUnitaire');
        $quantite = Flight::request()->data('quantite');
        $dateSaisie = date('Y-m-d');

        if($id) {
            $this->donModel->update($id, $donateur, $type, $designation, $montantUnitaire, $quantite, $dateSaisie);
            Flight::redirect('/dons?msg=updated');
        } else {
            $this->donModel->create($donateur, $type, $designation, $montantUnitaire, $quantite, $dateSaisie);
            Flight::redirect('/dons?msg=created');
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
}
?>
