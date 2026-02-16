<?php

use app\controllers\ApiExampleController;
use app\controllers\LayoutController;
use app\middlewares\SecurityHeadersMiddleware;
use flight\Engine;
use flight\net\Router;

/** 
 * @var Router $router 
 * @var Engine $app
 */

// This wraps all routes in the group with the SecurityHeadersMiddleware
$router->group('', function(Router $router) use ($app) {

	// Dashboard / Accueil
	$router->get('/', function() {
		$controller = new LayoutController();
		$controller->dashboard();
	});

	// Routes Villes
	$router->get('/villes', function() {
		$controller = new LayoutController();
		$controller->listVilles();
	});

	$router->get('/villes/create', function() {
		$controller = new LayoutController();
		$controller->createVille();
	});

	$router->get('/villes/@id/edit', function($id) {
		$controller = new LayoutController();
		$controller->editVille($id);
	});

	$router->post('/villes/save', function() {
		$controller = new LayoutController();
		$controller->saveVille();
	});

	$router->post('/villes/@id/delete', function($id) {
		$controller = new LayoutController();
		$controller->deleteVille($id);
	});

	// Routes Besoins
	$router->get('/besoins', function() {
		$controller = new LayoutController();
		$controller->listBesoins();
	});

	$router->get('/besoins/create', function() {
		$controller = new LayoutController();
		$controller->createBesoin();
	});

	$router->get('/besoins/@id/edit', function($id) {
		$controller = new LayoutController();
		$controller->editBesoin($id);
	});

	$router->post('/besoins/save', function() {
		$controller = new LayoutController();
		$controller->saveBesoin();
	});

	$router->post('/besoins/@id/delete', function($id) {
		$controller = new LayoutController();
		$controller->deleteBesoin($id);
	});

	// Routes Dons
	$router->get('/dons', function() {
		$controller = new LayoutController();
		$controller->listDons();
	});

	$router->get('/dons/create', function() {
		$controller = new LayoutController();
		$controller->createDon();
	});

	$router->get('/dons/@id/edit', function($id) {
		$controller = new LayoutController();
		$controller->editDon($id);
	});

	$router->post('/dons/save', function() {
		$controller = new LayoutController();
		$controller->saveDon();
	});

	$router->post('/dons/@id/delete', function($id) {
		$controller = new LayoutController();
		$controller->deleteDon($id);
	});

	// Routes Attributions
	$router->get('/attributions', function() {
		$controller = new LayoutController();
		$controller->listAttributions();
	});

	$router->get('/attributions/create', function() {
		$controller = new LayoutController();
		$controller->createAttribution();
	});

	$router->get('/attributions/@id/edit', function($id) {
		$controller = new LayoutController();
		$controller->editAttribution($id);
	});

	$router->post('/attributions/save', function() {
		$controller = new LayoutController();
		$controller->saveAttribution();
	});

	$router->post('/attributions/@id/delete', function($id) {
		$controller = new LayoutController();
		$controller->deleteAttribution($id);
	});

	// Routes Rapports
	$router->get('/dons/@id/rapport', function($id) {
		$controller = new LayoutController();
		$controller->rapportDistribution($id);
	});

	$router->post('/dons/@id/redistribuer', function($id) {
		$controller = new LayoutController();
		$controller->redistributre($id);
	});

	// ========== Routes Anciennes (API & Produits) ==========

	
	$router->get('/produit/@id', function($id) use ($app) {
		$controller = new ApiExampleController(Flight::app());
		$produit = $controller->getById($id);
		$app->render('produit', ['produit' => $produit]);
	});
	
	// $router->get('/', function() use ($app) {
	// 	$app->render('welcome', [ 'message' => 'You are gonna do great things!' ]);
	// });

	$router->get('/hello-world/@name', function($name) {
		echo '<h1>Hello world! Oh hey '.$name.'!</h1>';
	});

	$router->get('/recap', function() {
		$controller = new LayoutController();
		$controller->recap();
	});

	$router->get('/simulation', function() {
		$controller = new LayoutController();
		$controller->simulation();
	});

	$router->get('/stock-argent', function() {
		$controller = new LayoutController();
		$controller->listStockArgent();
	});

	$router->get('/stock-argent/repartition-historique', function() {
		$controller = new LayoutController();
		$controller->repartitionHistorique();
	});

	$router->group('/api', function() use ($router) {
		$router->get('/produits', [ ApiExampleController::class, 'getAll' ]);
		$router->get('/users', [ ApiExampleController::class, 'getUsers' ]);
		$router->get('/users/@id:[0-9]', [ ApiExampleController::class, 'getUser' ]);
		$router->post('/users/@id:[0-9]', [ ApiExampleController::class, 'updateUser' ]);
		$router->get('/recap', [LayoutController::class, 'recapApi']);
		$router->get('/simulation', [LayoutController::class, 'simulationApi']);
		$router->post('/simulation/valider', [LayoutController::class, 'validerSimulation']);
	});
	
}, [ SecurityHeadersMiddleware::class ]);