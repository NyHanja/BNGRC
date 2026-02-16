<?php
namespace app\models;
use PDO;

class StockArgentModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Récupérer le stock d'argent d'une ville
     */
    public function getByVille($idVille) {
        $stmt = $this->db->prepare("SELECT * FROM stockArgent WHERE idVille = :idVille");
        $stmt->bindParam(':idVille', $idVille, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Ajouter de l'argent au stock d'une ville (INSERT OR UPDATE)
     * Si la ville n'existe pas dans stockArgent → INSERT
     * Si existe déjà → UPDATE la quantité
     */
    public function ajouter($idVille, $montant) {
        // Utiliser INSERT ... ON DUPLICATE KEY UPDATE pour éviter les requêtes séparées
        $stmt = $this->db->prepare("
            INSERT INTO stockArgent (idVille, quantite) 
            VALUES (:idVille, :montant)
            ON DUPLICATE KEY UPDATE 
                quantite = quantite + :montant_update
        ");
        $stmt->bindParam(':idVille', $idVille, PDO::PARAM_INT);
        $stmt->bindParam(':montant', $montant, PDO::PARAM_INT);
        $stmt->bindParam(':montant_update', $montant, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Déduire de l'argent du stock d'une ville
     */
    public function deduire($idVille, $montant) {
        $stock = $this->getByVille($idVille);
        
        if (!$stock || $stock['quantite'] < $montant) {
            throw new \Exception('Stock insuffisant pour la ville ' . $idVille);
        }

        $stmt = $this->db->prepare("UPDATE stockArgent SET quantite = quantite - :montant WHERE idVille = :idVille");
        $stmt->bindParam(':montant', $montant, PDO::PARAM_INT);
        $stmt->bindParam(':idVille', $idVille, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Récupérer tous les stocks
     */
    public function getAll() {
        $stmt = $this->db->query("SELECT s.*, v.nom as villeName FROM stockArgent s 
                                 LEFT JOIN bngrc_villes v ON s.idVille = v.id
                                 ORDER BY v.nom");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Vérifier le stock d'une ville
     */
    public function verifierStock($idVille, $montantRequis) {
        $stock = $this->getByVille($idVille);
        
        if (!$stock) {
            return false;
        }
        
        return $stock['quantite'] >= $montantRequis;
    }
}
?>
