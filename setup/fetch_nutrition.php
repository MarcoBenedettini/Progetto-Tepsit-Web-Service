<?php
ini_set('memory_limit', '-1');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/IngredientNormalizer.php';
require_once __DIR__ . '/../src/UsdaClient.php';

$db = Database::get();
$normalizer = new IngredientNormalizer();
$usda = new UsdaClient();

$forceUpdate = in_array('--force-update', $argv) || in_array('-f', $argv);
$limit = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) $limit = (int)substr($arg, 8);
}

echo "============================================\n";
echo "Popolamento ingredienti e recipe_ingredients\n";
echo "Forza aggiornamento: " . ($forceUpdate ? 'SI' : 'NO') . "\n";
if ($limit) echo "Limite ricette: $limit\n";
echo "============================================\n";

$query = 'SELECT id, NER FROM recipes';
if ($limit) $query .= ' LIMIT ' . (int)$limit;
$stmt = $db->query($query);

$calls = 0;
$i = 0;
$newIngredients = 0;
$updatedIngredients = 0;
$linked = 0;

echo "Inizio elaborazione...\n";

while ($recipe = $stmt->fetch()) {
    $i++;
    $recipeId = (int)$recipe['id'];
    $names = $normalizer->fromNER((string) $recipe['NER']);
    $ingredientIds = [];

    foreach ($names as $name) {
        $check = $db->prepare('SELECT id, source FROM ingredients WHERE name = ?');
        $check->execute([$name]);
        $existing = $check->fetch();

        if ($existing) {
            if ($forceUpdate) {
                $usda->getMacros($name);
                $updatedIngredients++;
                $calls++;
                usleep(1_100_000);
            }
            $ingredientIds[] = $existing['id'];
        } else {
            $usda->getMacros($name);
            $newIngredients++;
            $calls++;
            usleep(1_100_000);

            $row = $db->prepare('SELECT id FROM ingredients WHERE name = ?');
            $row->execute([$name]);
            $ing = $row->fetch();
            if ($ing) $ingredientIds[] = $ing['id'];
        }
    }

    $numIngredients = count($ingredientIds);
    if ($numIngredients > 0) {
        // Distribuisce 100 grammi totali tra tutti gli ingredienti
        $quantityPerIngredient = round(100 / $numIngredients, 1);
        foreach ($ingredientIds as $ingId) {
            $link = $db->prepare('INSERT IGNORE INTO recipe_ingredients (recipe_id, ingredient_id, quantity_g) VALUES (?, ?, ?)');
            $link->execute([$recipeId, $ingId, $quantityPerIngredient]);
            $linked++;
        }
    }

    if ($i % 50 === 0) {
        echo "Ricette: $i | Chiamate USDA: $calls | Nuovi ingredienti: $newIngredients | Aggiornati: $updatedIngredients\n";
    }
}

echo "\n============================================\n";
echo "COMPLETATO\n";
echo "Ricette processate: $i\n";
echo "Chiamate USDA: $calls\n";
echo "Nuovi ingredienti: $newIngredients\n";
echo "Aggiornati (forzati): $updatedIngredients\n";
echo "Collegamenti recipe_ingredients: $linked\n";
echo "============================================\n";