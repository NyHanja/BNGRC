<?php
namespace app\models;
use PDO;

class VilleModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Récupérer toutes les villes
     */
    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM bngrc_villes ORDER BY nom ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer une ville par ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM bngrc_villes WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Créer une nouvelle ville
     */
    public function create($nom, $region) {
        $stmt = $this->db->prepare("INSERT INTO bngrc_villes (nom, region) VALUES (:nom, :region)");
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':region', $region);
        return $stmt->execute();
    }

    /**
     * Modifier une ville
     */
    public function update($id, $nom, $region) {
        $stmt = $this->db->prepare("UPDATE bngrc_villes SET nom = :nom, region = :region WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':region', $region);
        return $stmt->execute();
    }

    /**
     * Supprimer une ville
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM bngrc_villes WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Compter les villes
     */
    public function count() {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM bngrc_villes");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
}
?>
