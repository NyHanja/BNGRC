<?php
namespace app\services;
use app\models\DonModel;
use app\models\BesoinModel;
use app\models\VilleModel;
use app\models\AttributionModel;
use app\models\StockArgentModel;
use app\models\RepartitionArgentModel;
use app\models\StockDonModel;
use PDO;

class DistributionService {
    private $db;
    private $donModel;
    private $besoinModel;
    private $villeModel;
    private $attributionModel;
    private $stockArgentModel;
    private $repartitionArgentModel;
    private $stockDonModel;

    public function __construct($db) {
        $this->db = $db;
        $this->donModel = new DonModel($db);
        $this->besoinModel = new BesoinModel($db);
        $this->villeModel = new VilleModel($db);
        $this->attributionModel = new AttributionModel($db);
        $this->stockArgentModel = new StockArgentModel($db);
        $this->repartitionArgentModel = new RepartitionArgentModel($db);
        $this->stockDonModel = new StockDonModel($db);
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

            // 0️⃣ Utiliser le stock existant d'abord
            $stockExistant = $this->stockDonModel->getQuantite('argent', $designation);
            $montantRestant = $montantDon + $stockExistant;
            if ($stockExistant > 0) {
                $this->stockDonModel->deduire('argent', $designation, $stockExistant);
            }
            
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

                $this->repartitionArgentModel->creerRepartition(
                    $idBesoin,
                    $montantAlloue,
                    [$idVille => $montantAlloue]
                );

                $distribution[] = [
                    'idVille' => $idVille,
                    'ville' => $besoin['ville'],
                    'montant' => $montantAlloue,
                    'type' => 'besoin_satisfait'
                ];

                $montantRestant -= $montantAlloue;
            }

            // 3️⃣ S'il reste de l'argent → stocker dans bngrc_stock_dons
            if ($montantRestant > 0) {
                $this->stockDonModel->ajouter('argent', $designation, $montantRestant);
            }

            $this->db->commit();

            return [
                'success' => true,
                'don' => $don,
                'distribution' => $distribution,
                'montantTotal' => $montantDon,
                'montantRestant' => $montantRestant,
                'message' => 'Distribution argent réussie.' . ($montantRestant > 0 ? ' ' . $montantRestant . ' Ar stocké(s).' : '')
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

            // 0️⃣ Utiliser le stock existant d'abord
            $stockExistant = $this->stockDonModel->getQuantite($type, $designation);
            $quantiteRestante = (int)$don['quantite'] + $stockExistant;
            if ($stockExistant > 0) {
                $this->stockDonModel->deduire($type, $designation, $stockExistant);
            }

            // Si quantité totale = 0, rien à dispatcher
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

            // 2️⃣ S'il reste des dons → stocker dans bngrc_stock_dons (PAS de distribution équitable)
            if ($quantiteRestante > 0) {
                $this->stockDonModel->ajouter($type, $designation, $quantiteRestante);
            }

            $this->db->commit();

            return [
                'success' => true,
                'don' => $don,
                'distribution' => $distribution,
                'message' => 'Distribution réussie.' . ($quantiteRestante > 0 ? ' ' . $quantiteRestante . ' stocké(s) en réserve.' : '')
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
            $this->stockDonModel->viderTout();

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

            // 0️⃣ Utiliser le stock existant d'abord
            $stockExistant = $this->stockDonModel->getQuantite('argent', $designation);
            $montantRestant = $montantDon + $stockExistant;
            if ($stockExistant > 0) {
                $this->stockDonModel->deduire('argent', $designation, $stockExistant);
            }

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

                $distribution[] = [
                    'idVille' => $idVille,
                    'ville' => $besoin['ville'],
                    'montant' => $montantAlloue,
                    'type' => 'besoin_satisfait'
                ];

                $montantRestant -= $montantAlloue;
            }

            // Reste → stocker dans bngrc_stock_dons
            if ($montantRestant > 0) {
                $this->stockDonModel->ajouter('argent', $designation, $montantRestant);
            }

            $this->db->commit();
            return ['success' => true, 'distribution' => $distribution, 'message' => 'Distribution argent (plus petit besoin) réussie.' . ($montantRestant > 0 ? ' ' . $montantRestant . ' Ar stocké(s).' : '')];
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

            // 0️⃣ Utiliser le stock existant d'abord
            $stockExistant = $this->stockDonModel->getQuantite($type, $designation);
            $quantiteRestante = (int)$don['quantite'] + $stockExistant;
            if ($stockExistant > 0) {
                $this->stockDonModel->deduire($type, $designation, $stockExistant);
            }

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

            // Reste → stocker dans bngrc_stock_dons (PAS de distribution équitable)
            if ($quantiteRestante > 0) {
                $this->stockDonModel->ajouter($type, $designation, $quantiteRestante);
            }

            $this->db->commit();
            return ['success' => true, 'distribution' => $distribution, 'message' => 'Distribution (plus petit besoin) réussie.' . ($quantiteRestante > 0 ? ' ' . $quantiteRestante . ' stocké(s).' : '')];
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

            $totalDistribue = 0;

            foreach ($besoins as $besoin) {
                $besoinQte = (int)$besoin['quantite'];
                // Calcul proportionnel arrondi en bas
                $quantiteAttribuee = (int)floor($quantiteDon * $besoinQte / $totalBesoins);

                // Ne pas dépasser le besoin réel
                $quantiteAttribuee = min($quantiteAttribuee, $besoinQte);

                if ($quantiteAttribuee > 0) {
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

            // Le reste est stocké dans bngrc_stock_dons
            $resteNonDistribue = $quantiteDon - $totalDistribue;
            if ($resteNonDistribue > 0) {
                $this->stockDonModel->ajouter($type, $designation, $resteNonDistribue);
            }

            $this->db->commit();
            return [
                'success' => true,
                'distribution' => $distribution,
                'message' => 'Distribution proportionnelle réussie. ' . $totalDistribue . ' distribué(s)' . ($resteNonDistribue > 0 ? ', ' . $resteNonDistribue . ' stocké(s) en réserve' : '')
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

            $totalDistribue = 0;

            foreach ($besoinsArgent as $besoin) {
                $besoinMontant = (int)$besoin['montant'];
                // Calcul proportionnel arrondi en bas
                $montantAlloue = (int)floor($montantDon * $besoinMontant / $totalBesoins);

                // Ne pas dépasser le besoin réel
                $montantAlloue = min($montantAlloue, $besoinMontant);

                if ($montantAlloue > 0) {
                    $this->repartitionArgentModel->creerRepartition(
                        $besoin['idBesoin'],
                        $montantAlloue,
                        [$besoin['idVille'] => $montantAlloue]
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

            // Le reste est stocké dans bngrc_stock_dons
            $resteNonDistribue = $montantDon - $totalDistribue;
            if ($resteNonDistribue > 0) {
                $this->stockDonModel->ajouter('argent', $don['designation'], $resteNonDistribue);
            }

            $this->db->commit();
            return [
                'success' => true,
                'distribution' => $distribution,
                'message' => 'Distribution argent proportionnelle réussie. ' . $totalDistribue . ' Ar distribué(s)' . ($resteNonDistribue > 0 ? ', ' . $resteNonDistribue . ' Ar stocké(s) en réserve' : '')
            ];
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }

    /**
     * Distribuer les restes en stock vers les besoins non satisfaits
     * @param string $mode 'ancien' | 'plus_petit' | 'proportionnel'
     * @return array ['nbDistribue' => int]
     */
    private function distribuerDepuisStock($mode = 'ancien') {
        $stocks = $this->stockDonModel->getAll();
        $nbDistribue = 0;

        foreach ($stocks as $stock) {
            $type = $stock['type'];
            $designation = $stock['designation'];
            $quantite = (int)$stock['quantite'];
            if ($quantite <= 0) continue;

            try {
                $this->db->beginTransaction();

                if ($type === 'argent') {
                    $nbDistribue += $this->distribuerStockArgent($designation, $quantite, $mode);
                } else {
                    $nbDistribue += $this->distribuerStockClassique($type, $designation, $quantite, $mode);
                }

                $this->db->commit();
            } catch (\Exception $e) {
                if ($this->db->inTransaction()) $this->db->rollBack();
            }
        }

        return ['nbDistribue' => $nbDistribue];
    }

    /**
     * Distribuer un stock classique (nature/materiaux) vers les besoins non satisfaits
     */
    private function distribuerStockClassique($type, $designation, $quantite, $mode) {
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
            // Distribution proportionnelle
            $totalBesoins = 0;
            foreach ($besoins as $b) $totalBesoins += (int)$b['quantite'];
            if ($totalBesoins <= 0) return 0;

            $totalDistribue = 0;
            foreach ($besoins as $besoin) {
                $besoinQte = (int)$besoin['quantite'];
                $qte = (int)floor($quantite * $besoinQte / $totalBesoins);
                $qte = min($qte, $besoinQte);
                if ($qte > 0) {
                    $this->attributionModel->create(null, $besoin['idVille'], $designation, $qte, $dateAttribution);
                    $totalDistribue += $qte;
                    $nbAttrib++;
                }
            }
            $restant = $quantite - $totalDistribue;
        } else {
            // Distribution séquentielle (ancien ou plus_petit)
            foreach ($besoins as $besoin) {
                if ($restant <= 0) break;
                $qteNecessaire = (int)$besoin['quantite'];
                $qteAttribuee = min($restant, $qteNecessaire);

                $this->attributionModel->create(null, $besoin['idVille'], $designation, $qteAttribuee, $dateAttribution);
                $restant -= $qteAttribuee;
                $nbAttrib++;
            }
        }

        // Déduire ce qui a été distribué du stock
        $distribue = $quantite - $restant;
        if ($distribue > 0) {
            $this->stockDonModel->deduire($type, $designation, $distribue);
        }
        // Si reste encore, il reste en stock (inchangé)

        return $nbAttrib;
    }

    /**
     * Distribuer un stock argent vers les besoins argent non satisfaits
     */
    private function distribuerStockArgent($designation, $quantite, $mode) {
        if ($mode === 'plus_petit') {
            $besoins = $this->obtenirBesoinsArgentNonSatisfaitsPlusPetit();
        } else {
            $besoins = $this->obtenirBesoinsArgentNonSatisfaits();
        }

        if (empty($besoins)) return 0;

        $restant = $quantite;
        $nbAttrib = 0;

        if ($mode === 'proportionnel') {
            // Distribution proportionnelle
            $totalBesoins = 0;
            foreach ($besoins as $b) $totalBesoins += (int)$b['montant'];
            if ($totalBesoins <= 0) return 0;

            $totalDistribue = 0;
            foreach ($besoins as $besoin) {
                $besoinMontant = (int)$besoin['montant'];
                $montant = (int)floor($quantite * $besoinMontant / $totalBesoins);
                $montant = min($montant, $besoinMontant);
                if ($montant > 0) {
                    $this->repartitionArgentModel->creerRepartition(
                        $besoin['idBesoin'],
                        $montant,
                        [$besoin['idVille'] => $montant]
                    );
                    $totalDistribue += $montant;
                    $nbAttrib++;
                }
            }
            $restant = $quantite - $totalDistribue;
        } else {
            // Distribution séquentielle (ancien ou plus_petit)
            foreach ($besoins as $besoin) {
                if ($restant <= 0) break;
                $montantNecessaire = (int)$besoin['montant'];
                $montantAlloue = min($restant, $montantNecessaire);

                $this->repartitionArgentModel->creerRepartition(
                    $besoin['idBesoin'],
                    $montantAlloue,
                    [$besoin['idVille'] => $montantAlloue]
                );
                $restant -= $montantAlloue;
                $nbAttrib++;
            }
        }

        // Déduire ce qui a été distribué du stock
        $distribue = $quantite - $restant;
        if ($distribue > 0) {
            $this->stockDonModel->deduire('argent', $designation, $distribue);
        }

        return $nbAttrib;
    }
}
?>
