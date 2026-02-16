<?php
namespace app\models;
use PDO;

class AttributionModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Récupérer toutes les attributions
     */
    public function getAll() {
        $stmt = $this->db->query("SELECT a.*, 
                                         d.donateur, d.designation as don_designation, d.type,
                                         v.nom as ville 
                                  FROM bngrc_attributions a 
                                  LEFT JOIN bngrc_dons d ON a.idDons = d.id 
                                  LEFT JOIN bngrc_villes v ON a.idVille = v.id 
                                  ORDER BY a.dateAttribution DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer une attribution par ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT a.*, 
                                          d.donateur, d.designation as don_designation, d.type,
                                          v.nom as ville 
                                   FROM bngrc_attributions a 
                                   LEFT JOIN bngrc_dons d ON a.idDons = d.id 
                                   LEFT JOIN bngrc_villes v ON a.idVille = v.id 
                                   WHERE a.id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les attributions par don
     */
    public function getByDon($idDons) {
        $stmt = $this->db->prepare("SELECT a.*, v.nom as ville 
                                     FROM bngrc_attributions a 
                                     LEFT JOIN bngrc_villes v ON a.idVille = v.id 
                                     WHERE a.idDons = :idDons 
                                     ORDER BY a.dateAttribution DESC");
        $stmt->execute([':idDons' => $idDons]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les attributions par ville
     */
    public function getByVille($idVille) {
        $stmt = $this->db->prepare("SELECT a.*, d.donateur, d.designation as don_designation 
                                     FROM bngrc_attributions a 
                                     LEFT JOIN bngrc_dons d ON a.idDons = d.id 
                                     WHERE a.idVille = :idVille 
                                     ORDER BY a.dateAttribution DESC");
        $stmt->execute([':idVille' => $idVille]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Créer une nouvelle attribution
     */
    public function create($idDons, $idVille, $designation, $quantiteAttribuee, $dateAttribution) {
        $stmt = $this->db->prepare("INSERT INTO bngrc_attributions (idDons, idVille, designation, quantiteAttribuee, dateAttribution) 
                                     VALUES (:idDons, :idVille, :designation, :quantiteAttribuee, :dateAttribution)");
        return $stmt->execute([
            ':idDons' => $idDons,
            ':idVille' => $idVille,
            ':designation' => $designation,
            ':quantiteAttribuee' => $quantiteAttribuee,
            ':dateAttribution' => $dateAttribution
        ]);
    }

    /**
     * Modifier une attribution
     */
    public function update($id, $idDons, $idVille, $designation, $quantiteAttribuee, $dateAttribution) {
        $stmt = $this->db->prepare("UPDATE bngrc_attributions 
                                    SET idDons = :idDons, idVille = :idVille, designation = :designation, 
                                        quantiteAttribuee = :quantiteAttribuee, dateAttribution = :dateAttribution 
                                    WHERE id = :id");
        return $stmt->execute([
            ':id' => $id,
            ':idDons' => $idDons,
            ':idVille' => $idVille,
            ':designation' => $designation,
            ':quantiteAttribuee' => $quantiteAttribuee,
            ':dateAttribution' => $dateAttribution
        ]);
    }

    /**
     * Supprimer une attribution
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM bngrc_attributions WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Compter les attributions
     */
    public function count() {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM bngrc_attributions");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    /**
     * Récupérer les attributions avec statistiques
     */
    public function getStats() {
        $stmt = $this->db->query("SELECT 
                                    COUNT(*) as total_attributions,
                                    SUM(quantiteAttribuee) as total_quantite,
                                    COUNT(DISTINCT idVille) as villes_beneficiaires,
                                    COUNT(DISTINCT idDons) as dons_distribues
                                 FROM bngrc_attributions");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getRecap(){
        $stmt = $this->db->prepare("SELECT * FROM vue_besoins_non_satisfaits");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);   
    }
}

?>