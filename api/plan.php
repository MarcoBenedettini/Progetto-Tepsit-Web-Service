<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api/error.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Validator.php';
require_once __DIR__ . '/../src/RecipeRepository.php';
require_once __DIR__ . '/../src/DietPlanner.php';

header('Content-Type: application/json; charset=utf-8');

// Valida parametri
try {
    $params = (new Validator())->validate($_GET);
} catch (InvalidArgumentException $e) {
    sendError($e->getMessage(), 400);
}

// Genera piano
try {
    [$plan, $summary] = (new DietPlanner())->build($params);
} catch (Throwable $e) {
    sendError('Errore interno del server', 500);
}

// Risposta
echo json_encode([
    'success' => true,
    'inputs'  => $params,
    'summary' => $summary,
    'plan'    => $plan,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);