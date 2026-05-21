<?php
require_once __DIR__ . '/../api/error.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Validator.php';
require_once __DIR__ . '/../src/RecipeRepository.php';
require_once __DIR__ . '/../src/DietPlanner.php';

header('Content-Type: application/json; charset=utf-8');

// Validazione dei parametri
try {
    $validator = new Validator();
    $params = $validator->validate($_GET);
} catch (Exception $e) {
    sendError($e->getMessage(), 400);
}

// Generazione del piano pasti
try {
    $planner = new DietPlanner();
    $result = $planner->build($params);
    $plan = $result[0];
    $summary = $result[1];
} catch (Exception $e) {
    sendError('Errore interno del server', 500);
}

// Risposta JSON
$response = array(
    'success' => true,
    'inputs'  => $params,
    'summary' => $summary,
    'plan'    => $plan
);

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);