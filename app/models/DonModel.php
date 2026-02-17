<?php
namespace app\models;
use PDO;

class DonModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Récupérer tous les dons
     */
    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM bngrc_dons ORDER BY dateSaisie DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer un don par ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM bngrc_dons WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les dons par type
     */
    public function getByType($type) {
        $stmt = $this->db->prepare("SELECT * FROM bngrc_dons WHERE type = :type ORDER BY dateSaisie DESC");
        $stmt->execute([':type' => $type]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Créer un nouveau don
     */
    public function create($donateur, $type, $designation, $montantUnitaire, $quantite, $dateSaisie) {
        $stmt = $this->db->prepare("INSERT INTO bngrc_dons (donateur, type, designation, montantUnitaire, quantite, dateSaisie) 
                                     VALUES (:donateur, :type, :designation, :montantUnitaire, :quantite, :dateSaisie)");
        $result = $stmt->execute([
            ':donateur' => $donateur,
            ':type' => $type,
            ':designation' => $designation,
            ':montantUnitaire' => $montantUnitaire,
            ':quantite' => $quantite,
            ':dateSaisie' => $dateSaisie
        ]);
        
        // Retourner l'ID du don inséré
        return $result ? $this->db->lastInsertId() : false;
    }

    /**
     * Modifier un don
     */
    public function update($id, $donateur, $type, $designation, $montantUnitaire, $quantite, $dateSaisie) {
        $stmt = $this->db->prepare("UPDATE bngrc_dons SET donateur = :donateur, type = :type, designation = :designation, 
                                    montantUnitaire = :montantUnitaire, quantite = :quantite, dateSaisie = :dateSaisie WHERE id = :id");
        return $stmt->execute([
            ':id' => $id,
            ':donateur' => $donateur,
            ':type' => $type,
            ':designation' => $designation,
            ':montantUnitaire' => $montantUnitaire,
            ':quantite' => $quantite,
            ':dateSaisie' => $dateSaisie
        ]);
    }

    /**
     * Supprimer un don
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM bngrc_dons WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Compter les dons
     */
    public function count() {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM bngrc_dons");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    /**
     * Marquer un don comme dispatché
     */
    public function markDispatched($id) {
        $stmt = $this->db->prepare("UPDATE bngrc_dons SET dispatched = 1 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Marquer un don comme non-dispatché (annulation)
     */
    public function markUndispatched($id) {
        $stmt = $this->db->prepare("UPDATE bngrc_dons SET dispatched = 0 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Vérifier si un don est déjà dispatché
     */
    public function isDispatched($id) {
        $stmt = $this->db->prepare("SELECT dispatched FROM bngrc_dons WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['dispatched'] == 1;
    }
}
?>
