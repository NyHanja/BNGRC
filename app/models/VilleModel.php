<?php
namespace app\models;

use PDO;

class VilleModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }


    // ✅ CREATE
    // public function create($nom, $region) {
    //     $sql = "INSERT INTO ville (nom, region) VALUES (:nom, :region)";
    //     $stmt = $this->db->prepare($sql);
    //     return $stmt->execute([
    //         ':nom' => $nom,
    //         ':region' => $region
    //     ]);
    // }

    // // ✅ READ ALL
    // public function getAll() {
    //     $sql = "SELECT * FROM ville";
    //     $stmt = $this->db->query($sql);
    //     return $stmt->fetchAll(PDO::FETCH_ASSOC);
    // }

    // ✅ READ ONE
    // public function getById($id) {
    //     $sql = "SELECT * FROM ville WHERE id = :id";
    //     $stmt = $this->db->prepare($sql);
    //     $stmt->execute([':id' => $id]);
    //     return $stmt->fetch(PDO::FETCH_ASSOC);
    // }

    // ✅ UPDATE
    // public function update($id, $nom, $region) {
    //     $sql = "UPDATE ville SET nom = :nom, region = :region WHERE id = :id";
    //     $stmt = $this->db->prepare($sql);
    //     return $stmt->execute([
    //         ':id' => $id,
    //         ':nom' => $nom,
    //         ':region' => $region
    //     ]);
    // }

    // public function delete($id) {
    //     $sql = "DELETE FROM ville WHERE id = :id";
    //     $stmt = $this->db->prepare($sql);
    //     return $stmt->execute([':id' => $id]);
    // }
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
