<?php
namespace app\models;
use PDO;

class BesoinModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Récupérer tous les besoins
     */
    public function getAll() {
        $stmt = $this->db->query("SELECT b.*, v.nom as ville FROM bngrc_besoins b 
                                   LEFT JOIN bngrc_villes v ON b.idVille = v.id 
                                   ORDER BY b.dateSaisie DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer un besoin par ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT b.*, v.nom as ville FROM bngrc_besoins b 
                                     LEFT JOIN bngrc_villes v ON b.idVille = v.id 
                                     WHERE b.id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getVilleId($id) {
        $stmt = $this->db->prepare("SELECT * FROM bngrc_besoins WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les besoins par ville
     */
    public function getByVille($idVille) {
        $stmt = $this->db->prepare("SELECT * FROM bngrc_besoins WHERE idVille = :idVille 
                                     ORDER BY dateSaisie DESC");
        $stmt->execute([':idVille' => $idVille]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Créer un nouveau besoin
     */
    public function create($idVille, $type, $designation, $prixUnitaire, $quantite, $dateSaisie) {
        $stmt = $this->db->prepare("INSERT INTO bngrc_besoins (idVille, type, designation, prixUnitaire, quantite, dateSaisie) 
                                     VALUES (:idVille, :type, :designation, :prixUnitaire, :quantite, :dateSaisie)");
        return $stmt->execute([
            ':idVille' => $idVille,
            ':type' => $type,
            ':designation' => $designation,
            ':prixUnitaire' => $prixUnitaire,
            ':quantite' => $quantite,
            ':dateSaisie' => $dateSaisie
        ]);
    }

    /**
     * Modifier un besoin
     */
    public function update($id, $idVille, $type, $designation, $prixUnitaire, $quantite, $dateSaisie) {
        $stmt = $this->db->prepare("UPDATE bngrc_besoins SET idVille = :idVille, type = :type, designation = :designation, 
                                    prixUnitaire = :prixUnitaire, quantite = :quantite, dateSaisie = :dateSaisie WHERE id = :id");
        return $stmt->execute([
            ':id' => $id,
            ':idVille' => $idVille,
            ':type' => $type,
            ':designation' => $designation,
            ':prixUnitaire' => $prixUnitaire,
            ':quantite' => $quantite,
            ':dateSaisie' => $dateSaisie
        ]);
    }

    /**
     * Supprimer un besoin
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM bngrc_besoins WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Compter les besoins
     */
    public function count() {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM bngrc_besoins");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
}
?>
