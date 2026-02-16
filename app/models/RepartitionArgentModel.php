<?php
namespace app\models;

class RepartitionArgentModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Créer une repartition d'argent
     * @param int $idBesoin - ID du besoin d'argent
     * @param int $montantReparti - Montant total à répartir
     * @param array $repartion - ['idVille' => montant, ...]
     * @return bool
     */
    public function creerRepartition($idBesoin, $montantReparti, $repartition) {
        try {
            // Commencer transaction
            $this->db->beginTransaction();
            
            // Créer enregistrement repartition
            $stmt = $this->db->prepare("
                INSERT INTO repartitionArgent (idBesoin, montantReparti)
                VALUES (?, ?)
            ");
            $stmt->execute([$idBesoin, $montantReparti]);
            $idRepartition = $this->db->lastInsertId();
            
            // Ajouter détails repartition pour chaque ville
            $stmtDetail = $this->db->prepare("
                INSERT INTO detailsRepartition (idRepartition, idVille, montantRecu)
                VALUES (?, ?, ?)
            ");
            
            foreach ($repartition as $idVille => $montant) {
                $stmtDetail->execute([$idRepartition, $idVille, $montant]);
            }
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw new \Exception("Erreur repartition: " . $e->getMessage());
        }
    }

    /**
     * Répartir les argents d'un besoin satisfait entre toutes les villes
     * @param int $idBesoin - ID du besoin
     * @param int $montantTotal - Montant à répartir
     * @return array - Montants répartis par ville
     */
    public function repartirParVilles($idBesoin, $montantTotal) {
        try {
            // Récupérer toutes les villes
            $stmt = $this->db->prepare("
                SELECT id FROM bngrc_villes ORDER BY id
            ");
            $stmt->execute();
            $villes = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
            
            if (empty($villes)) {
                throw new \Exception("Aucune ville trouvée");
            }
            
            // Calculer montant par ville (division équitable)
            $montantParVille = intdiv($montantTotal, count($villes));
            $reste = $montantTotal % count($villes);
            
            // Préparer repartition
            $repartition = [];
            foreach ($villes as $index => $idVille) {
                $montant = $montantParVille;
                if ($index < $reste) {
                    $montant += 1; // Ajouter le reste aux premières villes
                }
                $repartition[$idVille] = $montant;
                
                // Ajouter à stockArgent
                $this->ajouterAuStock($idVille, $montant);
            }
            
            // Enregistrer la repartition
            $this->creerRepartition($idBesoin, $montantTotal, $repartition);
            
            return $repartition;
        } catch (\Exception $e) {
            throw new \Exception("Erreur répartition par villes: " . $e->getMessage());
        }
    }

    /**
     * Ajouter montant au stock d'une ville
     */
    private function ajouterAuStock($idVille, $montant) {
        $stmt = $this->db->prepare("
            INSERT INTO stockArgent (idVille, quantite)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE quantite = quantite + ?
        ");
        // Pour ON DUPLICATE KEY, on a besoin de 3 paramètres
        $stmt->execute([$idVille, $montant, $montant]);
    }

    /**
     * Récupérer l'historique de repartition pour un besoin
     */
    public function getRepartitionsByBesoin($idBesoin) {
        $stmt = $this->db->prepare("
            SELECT ra.id, ra.montantReparti, ra.dateRepartition,
                   GROUP_CONCAT(CONCAT(v.nom, ': ', dr.montantRecu, ' Ar') SEPARATOR ', ') as details
            FROM repartitionArgent ra
            LEFT JOIN detailsRepartition dr ON ra.id = dr.idRepartition
            LEFT JOIN bngrc_villes v ON dr.idVille = v.id
            WHERE ra.idBesoin = ?
            GROUP BY ra.id
            ORDER BY ra.dateRepartition DESC
        ");
        $stmt->execute([$idBesoin]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer l'historique complet de repartition
     */
    public function getHistorique() {
        $stmt = $this->db->prepare("
            SELECT ra.id, ra.idBesoin, ra.montantReparti, ra.dateRepartition,
                   COUNT(dr.id) as nbVillesRecipients,
                   GROUP_CONCAT(CONCAT(v.nom, ': ', dr.montantRecu, ' Ar') SEPARATOR ', ') as details
            FROM repartitionArgent ra
            LEFT JOIN detailsRepartition dr ON ra.id = dr.idRepartition
            LEFT JOIN bngrc_villes v ON dr.idVille = v.id
            GROUP BY ra.id
            ORDER BY ra.dateRepartition DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
?>