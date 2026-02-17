<?php
namespace app\services;
use app\models\DonModel;
use app\models\BesoinModel;
use app\models\VilleModel;
use app\models\AttributionModel;
use app\models\StockArgentModel;
use app\models\RepartitionArgentModel;
use PDO;

class DistributionService {
    private $db;
    private $donModel;
    private $besoinModel;
    private $villeModel;
    private $attributionModel;
    private $stockArgentModel;
    private $repartitionArgentModel;

    public function __construct($db) {
        $this->db = $db;
        $this->donModel = new DonModel($db);
        $this->besoinModel = new BesoinModel($db);
        $this->villeModel = new VilleModel($db);
        $this->attributionModel = new AttributionModel($db);
        $this->stockArgentModel = new StockArgentModel($db);
        $this->repartitionArgentModel = new RepartitionArgentModel($db);
    }

    public function distribuerDon($idDon) {
        try {
            // Récupérer le don
            $don = $this->donModel->getById($idDon);
            if (!$don) {
                throw new \Exception("Don non trouvé");
            }

            // Vérifier si le don est déjà dispatché
            if ($this->donModel->isDispatched($idDon)) {
                return [
                    'success' => false,
                    'message' => 'Ce don a déjà été dispatché. Annulez le dispatch avant de redistribuer.'
                ];
            }

            // Vérifier si c'est un don d'argent
            if (strtolower($don['type']) === 'argent') {
                $result = $this->distribuerDonArgent($idDon);
            } else {
                // Sinon, utiliser la distribution classique
                $result = $this->distribuerDonClassique($idDon);
            }

            // Marquer comme dispatché si succès
            if ($result['success']) {
                $this->donModel->markDispatched($idDon);
            }

            return $result;

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la distribution: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Distribution spécialisée pour les dons d'argent
     * 1. Cherche les villes avec besoins d'argent non satisfaits
     * 2. Envoie l'argent pour combler ces besoins (enregistre dans repartitionArgent)
     * 3. S'il reste de l'argent et plus aucun besoin → stocke dans stockArgent équitablement
     */
    private function distribuerDonArgent($idDon) {
        try {
            $this->db->beginTransaction();

            $don = $this->donModel->getById($idDon);
            $montantDon = (int)$don['montantUnitaire'];
            $designation = $don['designation'];
            $dateAttribution = date('Y-m-d');

            // 0️⃣ Utiliser le stock existant de CE don d'abord
            $stockExistant = (int)$don['stock'];
            $montantRestant = $montantDon + $stockExistant;
            
            // 1️⃣ Trouver les besoins d'argent non satisfaits
            $besoinsArgent = $this->obtenirBesoinsArgentNonSatisfaits();
            $distribution = [];

            // 2️⃣ Satisfaire les besoins d'argent par ordre d'ancienneté
            foreach ($besoinsArgent as $besoin) {
                if ($montantRestant <= 0) break;

                $idVille = $besoin['idVille'];
                $idBesoin = $besoin['idBesoin'];
                $montantNecessaire = (int)$besoin['montant'];
                $montantAlloue = min($montantRestant, $montantNecessaire);

                // Enregistrer dans repartitionArgent + detailsRepartition
                $this->repartitionArgentModel->creerRepartition(
                    $idBesoin,
                    $montantAlloue,
                    [$idVille => $montantAlloue]
                );

                // Enregistrer dans stockArgent par ville
                $this->stockArgentModel->ajouter($idVille, $montantAlloue);

                // Enregistrer dans bngrc_attributions
                $this->attributionModel->create(
                    $idDon,
                    $idVille,
                    $designation,
                    $montantAlloue,
                    $dateAttribution
                );

                $distribution[] = [
                    'idVille' => $idVille,
                    'ville' => $besoin['ville'],
                    'montant' => $montantAlloue,
                    'type' => 'besoin_satisfait'
                ];

                $montantRestant -= $montantAlloue;
            }

            // 3️⃣ Mettre à jour le stock du don
            $this->donModel->setStock($idDon, $montantRestant);

            $this->db->commit();

            return [
                'success' => true,
                'don' => $don,
                'distribution' => $distribution,
                'montantTotal' => $montantDon,
                'montantRestant' => $montantRestant,
                'message' => 'Distribution argent réussie.' . ($montantRestant > 0 ? ' ' . $montantRestant . ' Ar stocké(s) dans le don.' : '')
            ];

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => 'Erreur distribution argent: ' . $e->getMessage()];
        }
    }

    /**
     * Distribution classique pour les dons (produits)
     */
    private function distribuerDonClassique($idDon) {
        try {
            $this->db->beginTransaction();

            $don = $this->donModel->getById($idDon);
            $type = $don['type'];
            $designation = $don['designation'];
            $dateAttribution = date('Y-m-d');
            $distribution = [];

            // 0️⃣ Utiliser le stock existant de CE don d'abord
            $stockExistant = (int)$don['stock'];
            $quantiteRestante = (int)$don['quantite'] + $stockExistant;

            if ($quantiteRestante <= 0) {
                $this->db->commit();
                return ['success' => true, 'distribution' => [], 'message' => 'Quantité disponible = 0, rien à distribuer'];
            }

            // 1️⃣ Trouver les besoins NON SATISFAITS le plus ancien
            $besoinsNonSatisfaits = $this->obtenirBesoinsNonSatisfaits($type, $designation);

            foreach ($besoinsNonSatisfaits as $besoin) {
                if ($quantiteRestante <= 0) break;

                $quantiteNecessaire = $besoin['quantite'];
                $quantiteAttribuee = min($quantiteRestante, $quantiteNecessaire);

                $this->attributionModel->create(
                    $idDon,
                    $besoin['idVille'],
                    $designation,
                    $quantiteAttribuee,
                    $dateAttribution
                );

                $distribution[] = [
                    'idVille' => $besoin['idVille'],
                    'ville' => $besoin['ville'],
                    'quantite' => $quantiteAttribuee,
                    'type' => 'besoin_satisfait'
                ];

                $quantiteRestante -= $quantiteAttribuee;
            }

            // 2️⃣ Mettre à jour le stock du don avec le reste
            $this->donModel->setStock($idDon, $quantiteRestante);

            $this->db->commit();

            return [
                'success' => true,
                'don' => $don,
                'distribution' => $distribution,
                'message' => 'Distribution réussie.' . ($quantiteRestante > 0 ? ' ' . $quantiteRestante . ' stocké(s) dans le don.' : '')
            ];

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => 'Erreur distribution: ' . $e->getMessage()];
        }
    }

    /**
     * Obtenir les besoins d'argent non satisfaits
     * Retourne les besoins d'argent triés par date d'ancienneté
     */
    private function obtenirBesoinsArgentNonSatisfaits() {
        $stmt = $this->db->prepare("
            SELECT 
                b.id as idBesoin, 
                b.idVille, 
                v.nom as ville,
                b.prixUnitaire * b.quantite as montant,
                COALESCE(SUM(ra.montantReparti), 0) as montantReparti,
                (b.prixUnitaire * b.quantite - COALESCE(SUM(ra.montantReparti), 0)) as montantRestant
            FROM bngrc_besoins b
            LEFT JOIN bngrc_villes v ON b.idVille = v.id
            LEFT JOIN repartitionArgent ra ON ra.idBesoin = b.id
            WHERE b.type = 'argent'
            GROUP BY b.id, b.idVille, v.nom, b.prixUnitaire, b.quantite
            HAVING (b.prixUnitaire * b.quantite - COALESCE(SUM(ra.montantReparti), 0)) > 0
            ORDER BY b.dateSaisie ASC
        ");

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(function($row) {
            return [
                'idBesoin' => $row['idBesoin'],
                'idVille' => (int)$row['idVille'],
                'ville' => $row['ville'],
                'montant' => (int)$row['montantRestant']
            ];
        }, $results);
    }

    private function obtenirBesoinsNonSatisfaits($type, $designation) {
        $stmt = $this->db->prepare("
            SELECT 
                b.id, 
                b.idVille, 
                v.nom as ville,
                b.designation,
                b.type,
                b.quantite,
                COALESCE(SUM(a.quantiteAttribuee), 0) as quantiteAttribuee,
                (b.quantite - COALESCE(SUM(a.quantiteAttribuee), 0)) as quantiteRestante
            FROM bngrc_besoins b
            LEFT JOIN bngrc_villes v ON b.idVille = v.id
            LEFT JOIN bngrc_attributions a ON a.idVille = b.idVille 
                AND a.designation = b.designation
            WHERE b.type = :type 
                AND b.designation = :designation
            GROUP BY b.id, b.idVille, v.nom, b.designation, b.type, b.quantite
            HAVING (b.quantite - COALESCE(SUM(a.quantiteAttribuee), 0)) > 0
            ORDER BY b.dateSaisie ASC
        ");

        $stmt->execute([':type' => $type, ':designation' => $designation]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mapper les résultats pour garder la compatibility
        return array_map(function($row) {
            return [
                'id' => $row['id'],
                'idVille' => $row['idVille'],
                'ville' => $row['ville'],
                'designation' => $row['designation'],
                'type' => $row['type'],
                'quantite' => $row['quantiteRestante']  // Utiliser la quantité restante
            ];
        }, $results);
    }

    private function obtenirVillesSansAttribution($idDon) {
        $stmt = $this->db->prepare("
            SELECT v.* FROM bngrc_villes v
            WHERE v.id NOT IN (
                SELECT DISTINCT idVille FROM bngrc_attributions WHERE idDons = :idDon
            )
            ORDER BY v.nom ASC
        ");

        $stmt->execute([':idDon' => $idDon]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenirRapportDistribution($idDon) {
        $don = $this->donModel->getById($idDon);
        $attributions = $this->attributionModel->getByDon($idDon);

        $totalDistribue = 0;
        foreach ($attributions as $attribution) {
            $totalDistribue += $attribution['quantiteAttribuee'];
        }

        return [
            'don' => $don,
            'attributions' => $attributions,
            'totalDistribue' => $totalDistribue,
            'reste' => $don['quantite'] - $totalDistribue,
            'pourcentageDistribue' => ($totalDistribue / $don['quantite']) * 100
        ];
    }

    public function redistribuerDon($idDon) {
        try {
            // Supprimer les anciennes attributions en dehors de la transaction de distribution
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("DELETE FROM bngrc_attributions WHERE idDons = :idDon");
            $stmt->execute([':idDon' => $idDon]);

            // Nettoyer le stock argent lié si c'est un don d'argent
            $don = $this->donModel->getById($idDon);
            if ($don && strtolower($don['type']) === 'argent') {
                // Vider stockArgent et repartitionArgent pour repartir de zéro
                $this->db->exec("DELETE FROM detailsRepartition");
                $this->db->exec("DELETE FROM repartitionArgent");
                $this->db->exec("DELETE FROM stockArgent");
            }
            $this->db->commit();

            // Redistribuer (cette méthode gère sa propre transaction)
            return $this->distribuerDon($idDon);

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return [
                'success' => false,
                'message' => 'Erreur lors de la redistribution: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Redistribuer TOUS les dons d'argent existants
     * Utile pour recalculer le stockArgent après correction de bugs
     */
    public function redistribuerTousDonsArgent() {
        try {
            // Nettoyer les anciens stocks
            $this->db->beginTransaction();
            $this->db->exec("DELETE FROM detailsRepartition");
            $this->db->exec("DELETE FROM repartitionArgent");
            $this->db->exec("DELETE FROM stockArgent");
            $this->db->commit();

            // Récupérer tous les dons d'argent
            $donsArgent = $this->donModel->getByType('argent');
            $resultats = [];

            foreach ($donsArgent as $don) {
                // Reset du statut pour permettre la redistribution
                $this->donModel->markUndispatched($don['id']);
                $resultat = $this->distribuerDonArgent($don['id']);
                if ($resultat['success']) {
                    $this->donModel->markDispatched($don['id']);
                }
                $resultats[] = $resultat;
            }

            return [
                'success' => true,
                'message' => count($donsArgent) . ' dons d\'argent redistribués',
                'details' => $resultats
            ];
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Annuler le dispatch d'un don
     * Supprime toutes les attributions liées et remet le don à l'état disponible
     */
    public function annulerDispatch($idDon) {
        try {
            $don = $this->donModel->getById($idDon);
            if (!$don) {
                throw new \Exception("Don non trouvé");
            }

            if (!$this->donModel->isDispatched($idDon)) {
                return [
                    'success' => false,
                    'message' => 'Ce don n\'est pas dispatché'
                ];
            }

            $this->db->beginTransaction();

            // Supprimer les attributions liées à ce don
            $stmt = $this->db->prepare("DELETE FROM bngrc_attributions WHERE idDons = :idDon");
            $stmt->execute([':idDon' => $idDon]);

            // Si c'est un don d'argent, nettoyer le stock et les répartitions
            if (strtolower($don['type']) === 'argent') {
                // Recalculer tout le stockArgent et répartitions
                $this->db->exec("DELETE FROM detailsRepartition");
                $this->db->exec("DELETE FROM repartitionArgent");
                $this->db->exec("DELETE FROM stockArgent");
            }

            // Marquer le don comme non-dispatché
            $this->donModel->markUndispatched($idDon);

            $this->db->commit();

            // Si c'était un don d'argent, redistribuer les AUTRES dons d'argent encore dispatchés
            if (strtolower($don['type']) === 'argent') {
                $donsArgent = $this->donModel->getByType('argent');
                foreach ($donsArgent as $autreDon) {
                    if ($autreDon['id'] != $idDon && $autreDon['dispatched'] == 1) {
                        $this->donModel->markUndispatched($autreDon['id']);
                        $this->distribuerDonArgent($autreDon['id']);
                        $this->donModel->markDispatched($autreDon['id']);
                    }
                }
            }

            return [
                'success' => true,
                'message' => 'Dispatch annulé avec succès. Le don est maintenant disponible pour un nouveau dispatch.'
            ];

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'annulation: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Annuler le dispatch de TOUS les dons
     */
    public function annulerTousDispatches() {
        try {
            $this->db->beginTransaction();

            // Supprimer toutes les attributions
            $this->db->exec("DELETE FROM bngrc_attributions");

            // Nettoyer stocks argent
            $this->db->exec("DELETE FROM detailsRepartition");
            $this->db->exec("DELETE FROM repartitionArgent");
            $this->db->exec("DELETE FROM stockArgent");

            // Vider le stock de dons restants
            $this->donModel->resetAllStock();

            // Remettre tous les dons en non-dispatché
            $this->db->exec("UPDATE bngrc_dons SET dispatched = 0");

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Tous les dispatches ont été annulés.'
            ];
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Dispatcher TOUS les dons non-dispatchés
     * Mode: par date ancienne (besoins les plus anciens en premier)
     */
    public function dispatcherTous() {
        try {
            // Récupérer tous les dons non dispatchés, triés par date la plus ancienne
            $stmt = $this->db->prepare("SELECT * FROM bngrc_dons WHERE dispatched = 0 ORDER BY dateSaisie ASC");
            $stmt->execute();
            $dons = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $nbDispatched = 0;
            $erreurs = [];

            foreach ($dons as $don) {
                $resultat = $this->distribuerDon($don['id']);
                if ($resultat['success']) {
                    $nbDispatched++;
                } else {
                    $erreurs[] = $don['donateur'] . ': ' . $resultat['message'];
                }
            }

            // Distribuer aussi depuis le stock restant
            $stockResult = $this->distribuerDepuisStock('ancien');

            $messages = [];
            if ($nbDispatched > 0) {
                $messages[] = $nbDispatched . ' don(s) dispatché(s)';
            }
            if ($stockResult['nbDistribue'] > 0) {
                $messages[] = $stockResult['nbDistribue'] . ' attribution(s) depuis le stock';
            }
            if (!empty($erreurs)) {
                $messages[] = 'Erreurs: ' . implode('; ', $erreurs);
            }

            $hasResult = $nbDispatched > 0 || $stockResult['nbDistribue'] > 0;
            return [
                'success' => $hasResult,
                'message' => $hasResult ? implode('. ', $messages) : 'Aucun don à dispatcher et aucun stock disponible.'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Dispatcher TOUS les dons non-dispatchés
     * Mode: plus petit besoin en premier (quantité la plus faible d'abord)
     */
    public function dispatcherTousPlusPetit() {
        try {
            // Récupérer tous les dons non dispatchés
            $stmt = $this->db->prepare("SELECT * FROM bngrc_dons WHERE dispatched = 0 ORDER BY dateSaisie ASC");
            $stmt->execute();
            $dons = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $nbDispatched = 0;
            $erreurs = [];

            foreach ($dons as $don) {
                $idDon = $don['id'];

                if ($this->donModel->isDispatched($idDon)) {
                    continue;
                }

                if (strtolower($don['type']) === 'argent') {
                    $result = $this->distribuerDonArgentPlusPetit($idDon);
                } else {
                    $result = $this->distribuerDonClassiquePlusPetit($idDon);
                }

                if ($result['success']) {
                    $this->donModel->markDispatched($idDon);
                    $nbDispatched++;
                } else {
                    $erreurs[] = $don['donateur'] . ': ' . $result['message'];
                }
            }

            // Distribuer aussi depuis le stock restant
            $stockResult = $this->distribuerDepuisStock('plus_petit');

            $messages = [];
            if ($nbDispatched > 0) {
                $messages[] = $nbDispatched . ' don(s) dispatché(s) (plus petit besoin en premier)';
            }
            if ($stockResult['nbDistribue'] > 0) {
                $messages[] = $stockResult['nbDistribue'] . ' attribution(s) depuis le stock';
            }
            if (!empty($erreurs)) {
                $messages[] = 'Erreurs: ' . implode('; ', $erreurs);
            }

            $hasResult = $nbDispatched > 0 || $stockResult['nbDistribue'] > 0;
            return [
                'success' => $hasResult,
                'message' => $hasResult ? implode('. ', $messages) : 'Aucun don à dispatcher et aucun stock disponible.'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Distribution argent : plus petit besoin en premier
     */
    private function distribuerDonArgentPlusPetit($idDon) {
        try {
            $this->db->beginTransaction();

            $don = $this->donModel->getById($idDon);
            $montantDon = (int)$don['montantUnitaire'];
            $designation = $don['designation'];
            $dateAttribution = date('Y-m-d');

            // 0️⃣ Utiliser le stock existant de CE don
            $stockExistant = (int)$don['stock'];
            $montantRestant = $montantDon + $stockExistant;

            $besoinsArgent = $this->obtenirBesoinsArgentNonSatisfaitsPlusPetit();
            $distribution = [];

            foreach ($besoinsArgent as $besoin) {
                if ($montantRestant <= 0) break;

                $idVille = $besoin['idVille'];
                $montantNecessaire = (int)$besoin['montant'];
                $montantAlloue = min($montantRestant, $montantNecessaire);

                $this->repartitionArgentModel->creerRepartition(
                    $besoin['idBesoin'],
                    $montantAlloue,
                    [$idVille => $montantAlloue]
                );

                // Enregistrer dans stockArgent par ville
                $this->stockArgentModel->ajouter($idVille, $montantAlloue);

                // Enregistrer dans bngrc_attributions
                $this->attributionModel->create(
                    $idDon,
                    $idVille,
                    $designation,
                    $montantAlloue,
                    $dateAttribution
                );

                $distribution[] = [
                    'idVille' => $idVille,
                    'ville' => $besoin['ville'],
                    'montant' => $montantAlloue,
                    'type' => 'besoin_satisfait'
                ];

                $montantRestant -= $montantAlloue;
            }

            // Mettre à jour le stock du don
            $this->donModel->setStock($idDon, $montantRestant);

            $this->db->commit();
            return ['success' => true, 'distribution' => $distribution, 'message' => 'Distribution argent (plus petit besoin) réussie.' . ($montantRestant > 0 ? ' ' . $montantRestant . ' Ar stocké(s) dans le don.' : '')];
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }

    /**
     * Distribution classique : plus petit besoin en quantité en premier
     */
    private function distribuerDonClassiquePlusPetit($idDon) {
        try {
            $this->db->beginTransaction();

            $don = $this->donModel->getById($idDon);
            $type = $don['type'];
            $designation = $don['designation'];
            $dateAttribution = date('Y-m-d');
            $distribution = [];

            // 0️⃣ Utiliser le stock existant de CE don
            $stockExistant = (int)$don['stock'];
            $quantiteRestante = (int)$don['quantite'] + $stockExistant;

            if ($quantiteRestante <= 0) {
                $this->db->commit();
                return ['success' => true, 'distribution' => [], 'message' => 'Quantité disponible = 0'];
            }

            $besoinsNonSatisfaits = $this->obtenirBesoinsNonSatisfaitsPlusPetit($type, $designation);

            foreach ($besoinsNonSatisfaits as $besoin) {
                if ($quantiteRestante <= 0) break;

                $quantiteNecessaire = $besoin['quantite'];
                $quantiteAttribuee = min($quantiteRestante, $quantiteNecessaire);

                $this->attributionModel->create(
                    $idDon,
                    $besoin['idVille'],
                    $designation,
                    $quantiteAttribuee,
                    $dateAttribution
                );

                $distribution[] = [
                    'idVille' => $besoin['idVille'],
                    'ville' => $besoin['ville'],
                    'quantite' => $quantiteAttribuee,
                    'type' => 'besoin_satisfait'
                ];

                $quantiteRestante -= $quantiteAttribuee;
            }

            // Mettre à jour le stock du don
            $this->donModel->setStock($idDon, $quantiteRestante);

            $this->db->commit();
            return ['success' => true, 'distribution' => $distribution, 'message' => 'Distribution (plus petit besoin) réussie.' . ($quantiteRestante > 0 ? ' ' . $quantiteRestante . ' stocké(s) dans le don.' : '')];
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }

    /**
     * Besoins d'argent non satisfaits triés par PLUS PETIT montant
     */
    private function obtenirBesoinsArgentNonSatisfaitsPlusPetit() {
        $stmt = $this->db->prepare("
            SELECT 
                b.id as idBesoin, 
                b.idVille, 
                v.nom as ville,
                b.prixUnitaire * b.quantite as montant,
                COALESCE(SUM(ra.montantReparti), 0) as montantReparti,
                (b.prixUnitaire * b.quantite - COALESCE(SUM(ra.montantReparti), 0)) as montantRestant
            FROM bngrc_besoins b
            LEFT JOIN bngrc_villes v ON b.idVille = v.id
            LEFT JOIN repartitionArgent ra ON ra.idBesoin = b.id
            WHERE b.type = 'argent'
            GROUP BY b.id, b.idVille, v.nom, b.prixUnitaire, b.quantite
            HAVING (b.prixUnitaire * b.quantite - COALESCE(SUM(ra.montantReparti), 0)) > 0
            ORDER BY montantRestant ASC
        ");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(function($row) {
            return ['idBesoin' => $row['idBesoin'], 'idVille' => (int)$row['idVille'], 'ville' => $row['ville'], 'montant' => (int)$row['montantRestant']];
        }, $results);
    }

    /**
     * Besoins non satisfaits triés par PLUS PETITE quantité
     */
    private function obtenirBesoinsNonSatisfaitsPlusPetit($type, $designation) {
        $stmt = $this->db->prepare("
            SELECT 
                b.id, 
                b.idVille, 
                v.nom as ville,
                b.designation,
                b.type,
                b.quantite,
                COALESCE(SUM(a.quantiteAttribuee), 0) as quantiteAttribuee,
                (b.quantite - COALESCE(SUM(a.quantiteAttribuee), 0)) as quantiteRestante
            FROM bngrc_besoins b
            LEFT JOIN bngrc_villes v ON b.idVille = v.id
            LEFT JOIN bngrc_attributions a ON a.idVille = b.idVille 
                AND a.designation = b.designation
            WHERE b.type = :type 
                AND b.designation = :designation
            GROUP BY b.id, b.idVille, v.nom, b.designation, b.type, b.quantite
            HAVING (b.quantite - COALESCE(SUM(a.quantiteAttribuee), 0)) > 0
            ORDER BY quantiteRestante ASC
        ");
        $stmt->execute([':type' => $type, ':designation' => $designation]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(function($row) {
            return ['id' => $row['id'], 'idVille' => $row['idVille'], 'ville' => $row['ville'], 'designation' => $row['designation'], 'type' => $row['type'], 'quantite' => $row['quantiteRestante']];
        }, $results);
    }

    /**
     * Dispatcher TOUS les dons par PROPORTIONNALITÉ
     * Chaque besoin reçoit une part proportionnelle à sa quantité par rapport au total des besoins.
     * On arrondit en bas (floor). Le reste non distribué est conservé chez le donateur.
     */
    public function dispatcherTousProportionnel() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM bngrc_dons WHERE dispatched = 0 ORDER BY dateSaisie ASC");
            $stmt->execute();
            $dons = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $nbDispatched = 0;
            $erreurs = [];

            foreach ($dons as $don) {
                $idDon = $don['id'];

                if ($this->donModel->isDispatched($idDon)) {
                    continue;
                }

                if (strtolower($don['type']) === 'argent') {
                    $result = $this->distribuerDonArgentProportionnel($idDon);
                } else {
                    $result = $this->distribuerDonClassiqueProportionnel($idDon);
                }

                if ($result['success']) {
                    $this->donModel->markDispatched($idDon);
                    $nbDispatched++;
                } else {
                    $erreurs[] = $don['donateur'] . ': ' . $result['message'];
                }
            }

            // Distribuer aussi depuis le stock restant
            $stockResult = $this->distribuerDepuisStock('proportionnel');

            $messages = [];
            if ($nbDispatched > 0) {
                $messages[] = $nbDispatched . ' don(s) dispatché(s) par proportionnalité';
            }
            if ($stockResult['nbDistribue'] > 0) {
                $messages[] = $stockResult['nbDistribue'] . ' attribution(s) depuis le stock';
            }
            if (!empty($erreurs)) {
                $messages[] = 'Erreurs: ' . implode('; ', $erreurs);
            }

            $hasResult = $nbDispatched > 0 || $stockResult['nbDistribue'] > 0;
            return [
                'success' => $hasResult,
                'message' => $hasResult ? implode('. ', $messages) : 'Aucun don à dispatcher et aucun stock disponible.'
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }

    /**
     * Distribution classique proportionnelle (nature/matériaux)
     * Chaque besoin reçoit : floor(quantiteDon * besoinVille / totalBesoins)
     * Le reste est conservé chez le donateur (non distribué)
     */
    private function distribuerDonClassiqueProportionnel($idDon) {
        try {
            $this->db->beginTransaction();

            $don = $this->donModel->getById($idDon);
            $quantiteDon = (int)$don['quantite'];
            $type = $don['type'];
            $designation = $don['designation'];
            $dateAttribution = date('Y-m-d');
            $distribution = [];

            // Récupérer tous les besoins non satisfaits pour ce type/designation
            $besoins = $this->obtenirBesoinsNonSatisfaits($type, $designation);

            if (empty($besoins)) {
                $this->db->commit();
                return ['success' => true, 'distribution' => [], 'message' => 'Aucun besoin à satisfaire, don conservé'];
            }

            // Calculer le total des besoins
            $totalBesoins = 0;
            foreach ($besoins as $besoin) {
                $totalBesoins += (int)$besoin['quantite'];
            }

            if ($totalBesoins <= 0) {
                $this->db->commit();
                return ['success' => true, 'distribution' => [], 'message' => 'Total besoins = 0, don conservé'];
            }

            // === Méthode du plus grand reste (largest remainder) ===
            // 1. Calcul des parts exactes et des planchers
            $allocations = [];
            $totalFloor = 0;
            foreach ($besoins as $i => $besoin) {
                $besoinQte = (int)$besoin['quantite'];
                $exactShare = $quantiteDon * $besoinQte / $totalBesoins;
                $floorVal = (int)floor($exactShare);
                $floorVal = min($floorVal, $besoinQte); // Ne pas dépasser le besoin
                $fractional = $exactShare - floor($exactShare);
                $allocations[$i] = [
                    'besoin' => $besoin,
                    'floor' => $floorVal,
                    'fractional' => $fractional,
                    'maxExtra' => $besoinQte - $floorVal, // marge avant de dépasser le besoin
                ];
                $totalFloor += $floorVal;
            }

            // 2. Calculer le reste à distribuer
            $reste = $quantiteDon - $totalFloor;

            // 3. Trier par partie fractionnaire décroissante
            usort($allocations, function($a, $b) {
                return $b['fractional'] <=> $a['fractional'];
            });

            // 4. Distribuer +1 aux villes avec le plus grand reste fractionnaire
            foreach ($allocations as &$alloc) {
                if ($reste <= 0) break;
                if ($alloc['maxExtra'] > 0) {
                    $alloc['floor'] += 1;
                    $reste--;
                }
            }
            unset($alloc);

            // 5. Créer les attributions
            $totalDistribue = 0;
            foreach ($allocations as $alloc) {
                $quantiteAttribuee = $alloc['floor'];
                if ($quantiteAttribuee > 0) {
                    $besoin = $alloc['besoin'];
                    $this->attributionModel->create(
                        $idDon,
                        $besoin['idVille'],
                        $designation,
                        $quantiteAttribuee,
                        $dateAttribution
                    );

                    $distribution[] = [
                        'idVille' => $besoin['idVille'],
                        'ville' => $besoin['ville'],
                        'quantite' => $quantiteAttribuee,
                        'type' => 'proportionnel'
                    ];

                    $totalDistribue += $quantiteAttribuee;
                }
            }

            // Le reste éventuel (si besoins < don) est stocké dans le don
            $resteNonDistribue = $quantiteDon - $totalDistribue;
            $this->donModel->setStock($idDon, $resteNonDistribue);

            $this->db->commit();
            return [
                'success' => true,
                'distribution' => $distribution,
                'message' => 'Distribution proportionnelle réussie. ' . $totalDistribue . ' distribué(s)' . ($resteNonDistribue > 0 ? ', ' . $resteNonDistribue . ' stocké(s) dans le don' : '')
            ];
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }

    /**
     * Distribution argent proportionnelle
     * Chaque besoin reçoit : floor(montantDon * besoinMontant / totalBesoinsArgent)
     * Le reste est conservé chez le donateur (pas de stockArgent)
     */
    private function distribuerDonArgentProportionnel($idDon) {
        try {
            $this->db->beginTransaction();

            $don = $this->donModel->getById($idDon);
            $montantDon = (int)$don['montantUnitaire'];

            // Besoins d'argent non satisfaits
            $besoinsArgent = $this->obtenirBesoinsArgentNonSatisfaits();
            $distribution = [];

            if (empty($besoinsArgent)) {
                $this->db->commit();
                return ['success' => true, 'distribution' => [], 'message' => 'Aucun besoin argent, don conservé'];
            }

            // Total des besoins argent
            $totalBesoins = 0;
            foreach ($besoinsArgent as $besoin) {
                $totalBesoins += (int)$besoin['montant'];
            }

            if ($totalBesoins <= 0) {
                $this->db->commit();
                return ['success' => true, 'distribution' => [], 'message' => 'Total besoins argent = 0, don conservé'];
            }

            // === Méthode du plus grand reste (largest remainder) ===
            // 1. Calcul des parts exactes et des planchers
            $allocations = [];
            $totalFloor = 0;
            foreach ($besoinsArgent as $i => $besoin) {
                $besoinMontant = (int)$besoin['montant'];
                $exactShare = $montantDon * $besoinMontant / $totalBesoins;
                $floorVal = (int)floor($exactShare);
                $floorVal = min($floorVal, $besoinMontant);
                $fractional = $exactShare - floor($exactShare);
                $allocations[$i] = [
                    'besoin' => $besoin,
                    'floor' => $floorVal,
                    'fractional' => $fractional,
                    'maxExtra' => $besoinMontant - $floorVal,
                ];
                $totalFloor += $floorVal;
            }

            // 2. Calculer le reste à distribuer
            $reste = $montantDon - $totalFloor;

            // 3. Trier par partie fractionnaire décroissante
            usort($allocations, function($a, $b) {
                return $b['fractional'] <=> $a['fractional'];
            });

            // 4. Distribuer +1 aux villes avec le plus grand reste fractionnaire
            foreach ($allocations as &$alloc) {
                if ($reste <= 0) break;
                if ($alloc['maxExtra'] > 0) {
                    $alloc['floor'] += 1;
                    $reste--;
                }
            }
            unset($alloc);

            // 5. Créer les attributions
            $totalDistribue = 0;
            foreach ($allocations as $alloc) {
                $montantAlloue = $alloc['floor'];
                if ($montantAlloue > 0) {
                    $besoin = $alloc['besoin'];

                    $this->repartitionArgentModel->creerRepartition(
                        $besoin['idBesoin'],
                        $montantAlloue,
                        [$besoin['idVille'] => $montantAlloue]
                    );

                    $this->stockArgentModel->ajouter($besoin['idVille'], $montantAlloue);

                    $this->attributionModel->create(
                        $idDon,
                        $besoin['idVille'],
                        $don['designation'],
                        $montantAlloue,
                        date('Y-m-d')
                    );

                    $distribution[] = [
                        'idVille' => $besoin['idVille'],
                        'ville' => $besoin['ville'],
                        'montant' => $montantAlloue,
                        'type' => 'proportionnel'
                    ];

                    $totalDistribue += $montantAlloue;
                }
            }

            // Le reste éventuel est stocké dans le don
            $resteNonDistribue = $montantDon - $totalDistribue;
            $this->donModel->setStock($idDon, $resteNonDistribue);

            $this->db->commit();
            return [
                'success' => true,
                'distribution' => $distribution,
                'message' => 'Distribution argent proportionnelle réussie. ' . $totalDistribue . ' Ar distribué(s)' . ($resteNonDistribue > 0 ? ', ' . $resteNonDistribue . ' Ar stocké(s) dans le don' : '')
            ];
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }

    /**
     * Distribuer les restes en stock (don.stock > 0) vers les besoins non satisfaits
     * @param string $mode 'ancien' | 'plus_petit' | 'proportionnel'
     * @return array ['nbDistribue' => int]
     */
    private function distribuerDepuisStock($mode = 'ancien') {
        $donsAvecStock = $this->donModel->getDonsAvecStock();
        $nbDistribue = 0;

        foreach ($donsAvecStock as $don) {
            $idDon = $don['id'];
            $type = $don['type'];
            $designation = $don['designation'];
            $stockDispo = (int)$don['stock'];
            if ($stockDispo <= 0) continue;

            try {
                $this->db->beginTransaction();

                if (strtolower($type) === 'argent') {
                    $nbDistribue += $this->distribuerStockArgent($idDon, $designation, $stockDispo, $mode);
                } else {
                    $nbDistribue += $this->distribuerStockClassique($idDon, $type, $designation, $stockDispo, $mode);
                }

                $this->db->commit();
            } catch (\Exception $e) {
                if ($this->db->inTransaction()) $this->db->rollBack();
            }
        }

        return ['nbDistribue' => $nbDistribue];
    }

    /**
     * Distribuer le stock d'un don classique (nature/materiaux) vers les besoins non satisfaits
     */
    private function distribuerStockClassique($idDon, $type, $designation, $quantite, $mode) {
        if ($mode === 'plus_petit') {
            $besoins = $this->obtenirBesoinsNonSatisfaitsPlusPetit($type, $designation);
        } else {
            $besoins = $this->obtenirBesoinsNonSatisfaits($type, $designation);
        }

        if (empty($besoins)) return 0;

        $restant = $quantite;
        $nbAttrib = 0;
        $dateAttribution = date('Y-m-d');

        if ($mode === 'proportionnel') {
            $totalBesoins = 0;
            foreach ($besoins as $b) $totalBesoins += (int)$b['quantite'];
            if ($totalBesoins <= 0) return 0;

            // Largest remainder method
            $allocations = [];
            $totalFloor = 0;
            foreach ($besoins as $i => $b) {
                $besoinQte = (int)$b['quantite'];
                $exactShare = $quantite * $besoinQte / $totalBesoins;
                $floorVal = min((int)floor($exactShare), $besoinQte);
                $fractional = $exactShare - floor($exactShare);
                $allocations[$i] = ['besoin' => $b, 'floor' => $floorVal, 'fractional' => $fractional, 'maxExtra' => $besoinQte - $floorVal];
                $totalFloor += $floorVal;
            }
            $resteBonus = $quantite - $totalFloor;
            usort($allocations, fn($a, $b) => $b['fractional'] <=> $a['fractional']);
            foreach ($allocations as &$al) {
                if ($resteBonus <= 0) break;
                if ($al['maxExtra'] > 0) { $al['floor']++; $resteBonus--; }
            }
            unset($al);

            $totalDistribue = 0;
            foreach ($allocations as $al) {
                $qte = $al['floor'];
                if ($qte > 0) {
                    $this->attributionModel->create($idDon, $al['besoin']['idVille'], $designation, $qte, $dateAttribution);
                    $totalDistribue += $qte;
                    $nbAttrib++;
                }
            }
            $restant = $quantite - $totalDistribue;
        } else {
            foreach ($besoins as $besoin) {
                if ($restant <= 0) break;
                $qteNecessaire = (int)$besoin['quantite'];
                $qteAttribuee = min($restant, $qteNecessaire);

                $this->attributionModel->create($idDon, $besoin['idVille'], $designation, $qteAttribuee, $dateAttribution);
                $restant -= $qteAttribuee;
                $nbAttrib++;
            }
        }

        // Mettre à jour le stock du don
        $this->donModel->setStock($idDon, $restant);

        return $nbAttrib;
    }

    /**
     * Distribuer le stock d'un don argent vers les besoins argent non satisfaits
     */
    private function distribuerStockArgent($idDon, $designation, $quantite, $mode) {
        if ($mode === 'plus_petit') {
            $besoins = $this->obtenirBesoinsArgentNonSatisfaitsPlusPetit();
        } else {
            $besoins = $this->obtenirBesoinsArgentNonSatisfaits();
        }

        if (empty($besoins)) return 0;

        $restant = $quantite;
        $nbAttrib = 0;
        $dateAttribution = date('Y-m-d');

        if ($mode === 'proportionnel') {
            $totalBesoins = 0;
            foreach ($besoins as $b) $totalBesoins += (int)$b['montant'];
            if ($totalBesoins <= 0) return 0;

            // Largest remainder method
            $allocations = [];
            $totalFloor = 0;
            foreach ($besoins as $i => $b) {
                $besoinMontant = (int)$b['montant'];
                $exactShare = $quantite * $besoinMontant / $totalBesoins;
                $floorVal = min((int)floor($exactShare), $besoinMontant);
                $fractional = $exactShare - floor($exactShare);
                $allocations[$i] = ['besoin' => $b, 'floor' => $floorVal, 'fractional' => $fractional, 'maxExtra' => $besoinMontant - $floorVal];
                $totalFloor += $floorVal;
            }
            $resteBonus = $quantite - $totalFloor;
            usort($allocations, fn($a, $b) => $b['fractional'] <=> $a['fractional']);
            foreach ($allocations as &$al) {
                if ($resteBonus <= 0) break;
                if ($al['maxExtra'] > 0) { $al['floor']++; $resteBonus--; }
            }
            unset($al);

            $totalDistribue = 0;
            foreach ($allocations as $al) {
                $montant = $al['floor'];
                if ($montant > 0) {
                    $this->repartitionArgentModel->creerRepartition(
                        $al['besoin']['idBesoin'],
                        $montant,
                        [$al['besoin']['idVille'] => $montant]
                    );
                    $this->stockArgentModel->ajouter($al['besoin']['idVille'], $montant);
                    $this->attributionModel->create($idDon, $al['besoin']['idVille'], $designation, $montant, $dateAttribution);
                    $totalDistribue += $montant;
                    $nbAttrib++;
                }
            }
            $restant = $quantite - $totalDistribue;
        } else {
            foreach ($besoins as $besoin) {
                if ($restant <= 0) break;
                $montantNecessaire = (int)$besoin['montant'];
                $montantAlloue = min($restant, $montantNecessaire);

                $this->repartitionArgentModel->creerRepartition(
                    $besoin['idBesoin'],
                    $montantAlloue,
                    [$besoin['idVille'] => $montantAlloue]
                );
                $this->stockArgentModel->ajouter($besoin['idVille'], $montantAlloue);
                $this->attributionModel->create($idDon, $besoin['idVille'], $designation, $montantAlloue, $dateAttribution);
                $restant -= $montantAlloue;
                $nbAttrib++;
            }
        }

        // Mettre à jour le stock du don
        $this->donModel->setStock($idDon, $restant);

        return $nbAttrib;
    }
}
?>
