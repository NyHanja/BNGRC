<?php
namespace app\models;
use PDO;

class StockDonModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Ajouter au stock (INSERT ou UPDATE si type+designation existe déjà)
     */
    public function ajouter($type, $designation, $quantite) {
        $stmt = $this->db->prepare("
            INSERT INTO bngrc_stock_dons (type, designation, quantite, datestock)
            VALUES (:type, :designation, :quantite, :datestock)
            ON DUPLICATE KEY UPDATE 
                quantite = quantite + VALUES(quantite),
                datestock = VALUES(datestock)
        ");
        return $stmt->execute([
            ':type' => $type,
            ':designation' => $designation,
            ':quantite' => (int)$quantite,
            ':datestock' => date('Y-m-d')
        ]);
    }

    /**
     * Récupérer le stock pour un type + désignation
     */
    public function getStock($type, $designation) {
        $stmt = $this->db->prepare("SELECT * FROM bngrc_stock_dons WHERE type = :type AND designation = :designation");
        $stmt->execute([':type' => $type, ':designation' => $designation]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer la quantité disponible en stock (0 si rien)
     */
    public function getQuantite($type, $designation) {
        $stock = $this->getStock($type, $designation);
        return $stock ? (int)$stock['quantite'] : 0;
    }

    /**
     * Déduire du stock
     */
    public function deduire($type, $designation, $quantite) {
        $stmt = $this->db->prepare("
            UPDATE bngrc_stock_dons 
            SET quantite = quantite - :quantite, datestock = :datestock
            WHERE type = :type AND designation = :designation AND quantite >= :quantite2
        ");
        return $stmt->execute([
            ':quantite' => (int)$quantite,
            ':quantite2' => (int)$quantite,
            ':type' => $type,
            ':designation' => $designation,
            ':datestock' => date('Y-m-d')
        ]);
    }

    /**
     * Récupérer tout le stock
     */
    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM bngrc_stock_dons WHERE quantite > 0 ORDER BY type, designation");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Vider tout le stock
     */
    public function viderTout() {
        return $this->db->exec("DELETE FROM bngrc_stock_dons");
    }
}
?>
