<?php

namespace app\controllers;

use flight\Engine;
use app\models\ProduitModel;

use Flight;
class ApiExampleController {

	protected Engine $app;

	public function __construct($app) {
		$this->app = $app;
	}
	
	public function getAll() {
		$ProduitModel = new ProduitModel(Flight::db());
		$produits = $ProduitModel->getAll();
		$data = [];
		foreach($produits as $produit) {
			$data[] = ['id' => $produit["id"], 'nom' => $produit["nom"], 'prix' => $produit["prix"], 'images' => $produit["images"]];
		}
		return $data;
	}

	public function getById($id) {
		$ProduitModel = new ProduitModel(Flight::db());
		$produit = $ProduitModel->getById($id);
		return $produit;
	}

	public function updateUser($id) {
		// You could actually update data from the database if you had one set up
		// $statement = $this->app->db()->runQuery("UPDATE users SET email = ? WHERE id = ?", [ $this->app->data['email'], $id ]);
		$this->app->json([ 'success' => true, 'id' => $id ], 200, true, 'utf-8', JSON_PRETTY_PRINT);
	}
}