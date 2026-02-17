<?php
    namespace app\models;
    use Flight;
    use PDO;

    class ProduitModel {
        private $db;

        public function __construct($db) {
            $this->db = $db;
        }
        
        public function getAll() {
            $stmt = $this->db->query("SELECT * FROM produit");
            
            $produits = [];
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $produits[] = [
                    'id' => $row['id'],
                    'nom' => $row['nom'],
                    'prix' => $row['prix'],
                    'images' => $row['images']
                ];
            }

            return $produits;
        }
        
        public function getById($id) {
            $stmt = $this->db->prepare("SELECT * FROM produit WHERE id = :id");
            $stmt->execute([':id' => $id]);
            
            $produit = [];
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $produit = [
                    'id' => $row['id'],
                    'nom' => $row['nom'],
                    'prix' => $row['prix'],
                    'images' => $row['images']
                ];
            return $produit;
        }
    }
?>