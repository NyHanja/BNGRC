<?php
namespace app\models;

use PDO;

class VilleModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // ✅ CREATE
    public function create($nom, $region) {
        $sql = "INSERT INTO ville (nom, region) VALUES (:nom, :region)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nom' => $nom,
            ':region' => $region
        ]);
    }

    // ✅ READ ALL
    public function getAll() {
        $sql = "SELECT * FROM ville";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✅ READ ONE
    public function getById($id) {
        $sql = "SELECT * FROM ville WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ✅ UPDATE
    public function update($id, $nom, $region) {
        $sql = "UPDATE ville SET nom = :nom, region = :region WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':nom' => $nom,
            ':region' => $region
        ]);
    }

    public function delete($id) {
        $sql = "DELETE FROM ville WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
?>
