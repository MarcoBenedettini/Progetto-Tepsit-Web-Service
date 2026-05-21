<?php

// Endpoint API per la generazione del piano pasti.
require_once __DIR__ . '/../config.php';       
require_once __DIR__ . '/../api/error.php';    
require_once __DIR__ . '/../src/Database.php';  
require_once __DIR__ . '/../src/Validator.php';
require_once __DIR__ . '/../src/RecipeRepository.php'; 
require_once __DIR__ . '/../src/DietPlanner.php';

header('Content-Type: application/json; charset=utf-8');
try {
    $validator = new Validator();
    $params = $validator->validate($_GET);
} catch (InvalidArgumentException $e) {
    sendError($e->getMessage(), 400);
}


try {
    $dietPlanner = new DietPlanner();
    $result = $dietPlanner->build($params);	// build -> array da 2 elementi: plan e summary
    $plan = $result[0];
    $summary = $result[1];
} catch (Throwable $e) {
    sendError('Errore interno del server', 500);
}


$response = array('success' => true,
    'inputs' => array(
    	'calories' => $params['calories'],
    	'allergies' => $params['allergies'],
    	'days' => $params['days'],
    	'snacks' => $params['snacks']),
    	'summary' => $summary,
    	'plan' => $plan,
	);


// Array in JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);