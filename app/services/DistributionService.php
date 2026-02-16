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

            // Vérifier si c'est un don d'argent
            if (strtolower($don['type']) === 'argent') {
                return $this->distribuerDonArgent($idDon);
            }

            // Sinon, utiliser la distribution classique
            return $this->distribuerDonClassique($idDon);

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

            // Récupérer le don d'argent
            $don = $this->donModel->getById($idDon);
            // Pour l'argent : le montant total = montantUnitaire (quantite ignorée)
            $montantDon = (int)$don['montantUnitaire'];
            $montantRestant = $montantDon;
            
            // 1️⃣ PREMIÈRE ÉTAPE: Trouver les besoins d'argent non satisfaits
            $besoinsArgent = $this->obtenirBesoinsArgentNonSatisfaits();
            $villesAyantBesoin = [];
            $distribution = [];

            // 2️⃣ Satisfaire les besoins d'argent par ordre d'ancienneté
            foreach ($besoinsArgent as $besoin) {
                if ($montantRestant <= 0) {
                    break;
                }

                $idVille = $besoin['idVille'];
                $idBesoin = $besoin['idBesoin'];
                $montantNecessaire = (int)$besoin['montant'];
                $montantAlloue = min($montantRestant, $montantNecessaire);

                // Enregistrer la répartition pour CE besoin (traçabilité)
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

                if (!in_array($idVille, $villesAyantBesoin)) {
                    $villesAyantBesoin[] = $idVille;
                }
                $montantRestant -= $montantAlloue;
            }

            // 3️⃣ S'il reste de l'argent et plus aucun besoin → stocker dans stockArgent
            if ($montantRestant > 0) {
                // Répartir entre les villes qui avaient des besoins, ou toutes les villes
                $villesDestinataires = [];
                if (!empty($villesAyantBesoin)) {
                    $villesDestinataires = $villesAyantBesoin;
                } else {
                    $toutesLesVilles = $this->villeModel->getAll();
                    foreach ($toutesLesVilles as $v) {
                        $villesDestinataires[] = (int)$v['id'];
                    }
                }

                if (!empty($villesDestinataires)) {
                    $nbVilles = count($villesDestinataires);
                    $montantParVille = intdiv($montantRestant, $nbVilles);
                    $reste = $montantRestant % $nbVilles;

                    foreach ($villesDestinataires as $index => $idVille) {
                        $montantSupp = $montantParVille + ($index < $reste ? 1 : 0);
                        if ($montantSupp > 0) {
                            $this->stockArgentModel->ajouter($idVille, $montantSupp);
                            
                            $villeInfo = $this->villeModel->getById($idVille);
                            $distribution[] = [
                                'idVille' => $idVille,
                                'ville' => $villeInfo['nom'],
                                'montant' => $montantSupp,
                                'type' => 'stock_reste'
                            ];
                        }
                    }
                    $montantRestant = 0;
                }
            }

            $this->db->commit();

            return [
                'success' => true,
                'don' => $don,
                'distribution' => $distribution,
                'montantTotal' => $montantDon,
                'montantRestant' => $montantRestant,
                'message' => 'Distribution d\'argent réalisée avec succès'
            ];

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return [
                'success' => false,
                'message' => 'Erreur lors de la distribution d\'argent: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Distribution classique pour les dons (produits)
     */
    private function distribuerDonClassique($idDon) {
        try {
            $this->db->beginTransaction();

            // Récupérer le don
            $don = $this->donModel->getById($idDon);

            $quantiteRestante = $don['quantite'];
            $type = $don['type'];
            $designation = $don['designation'];
            $dateAttribution = date('Y-m-d');
            $distribution = [];

            // 1️⃣ PREMIÈRE ÉTAPE: Trouver la ville avec le besoin NON SATISFAIT le plus ancien
            $besoinsNonSatisfaits = $this->obtenirBesoinsNonSatisfaits($type, $designation);

            foreach ($besoinsNonSatisfaits as $besoin) {
                if ($quantiteRestante <= 0) {
                    break;
                }

                $quantiteNecessaire = $besoin['quantite'];
                $quantiteAttribuee = min($quantiteRestante, $quantiteNecessaire);

                // Créer l'attribution
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

            // 2️⃣ DEUXIÈME ÉTAPE: S'il reste des dons et aucun besoin spécifique
            if ($quantiteRestante > 0) {
                // Vérifier s'il y a des villes sans attribution pour ce don
                $villes = $this->villeModel->getAll();
                
                if (!empty($villes)) {
                    // Distribuer équitablement entre toutes les villes
                    $quantiteParVille = (int) ($quantiteRestante / count($villes));
                    $reste = $quantiteRestante % count($villes);

                    foreach ($villes as $index => $ville) {
                        $quantiteAttribuee = $quantiteParVille + ($index < $reste ? 1 : 0);

                        if ($quantiteAttribuee > 0) {
                            $this->attributionModel->create(
                                $idDon,
                                $ville['id'],
                                $designation,
                                $quantiteAttribuee,
                                $dateAttribution
                            );

                            $distribution[] = [
                                'idVille' => $ville['id'],
                                'ville' => $ville['nom'],
                                'quantite' => $quantiteAttribuee,
                                'type' => 'distribution_equitable'
                            ];
                        }
                    }
                }
            }

            $this->db->commit();

            return [
                'success' => true,
                'don' => $don,
                'distribution' => $distribution,
                'message' => 'Distribution automatique réalisée avec succès'
            ];

        } catch (\Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Erreur lors de la distribution: ' . $e->getMessage()
            ];
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

        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':designation', $designation);
        $stmt->execute();

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

        $stmt->bindParam(':idDon', $idDon, PDO::PARAM_INT);
        $stmt->execute();

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
            $stmt->bindParam(':idDon', $idDon, PDO::PARAM_INT);
            $stmt->execute();

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
                $resultat = $this->distribuerDonArgent($don['id']);
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
}
?>
