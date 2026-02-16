<?php
namespace app\services;
use app\models\DonModel;
use app\models\BesoinModel;
use app\models\VilleModel;
use app\models\AttributionModel;
use PDO;

class DistributionService {
    private $db;
    private $donModel;
    private $besoinModel;
    private $villeModel;
    private $attributionModel;

    public function __construct($db) {
        $this->db = $db;
        $this->donModel = new DonModel($db);
        $this->besoinModel = new BesoinModel($db);
        $this->villeModel = new VilleModel($db);
        $this->attributionModel = new AttributionModel($db);
    }

    public function distribuerDon($idDon) {
        try {
            $this->db->beginTransaction();

            // Récupérer le don
            $don = $this->donModel->getById($idDon);
            if (!$don) {
                throw new Exception("Don non trouvé");
            }

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

        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Erreur lors de la distribution: ' . $e->getMessage()
            ];
        }
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
            $this->db->beginTransaction();

            // Supprimer les anciennes attributions
            $stmt = $this->db->prepare("DELETE FROM bngrc_attributions WHERE idDons = :idDon");
            $stmt->bindParam(':idDon', $idDon, PDO::PARAM_INT);
            $stmt->execute();

            // Redistribuer
            $resultat = $this->distribuerDon($idDon);

            $this->db->commit();

            return $resultat;

        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Erreur lors de la redistribution: ' . $e->getMessage()
            ];
        }
    }
}
?>
