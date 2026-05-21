<?php
ini_set('memory_limit', '-1');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/IngredientNormalizer.php';
require_once __DIR__ . '/../src/UsdaClient.php';

$db = Database::get();
$normalizer = new IngredientNormalizer();
$usda = new UsdaClient();
$forceUpdate = false;
$limit = null;
foreach ($argv as $arg) {
    if ($arg === '--force-update' || $arg === '-f') {
        $forceUpdate = true;
    }
    if (strpos($arg, '--limit=') === 0) {
        $limit = substr($arg, 8);
    }
}


echo "Popolamento ingredienti e recipe_ingredients\n";
echo "Forza aggiornamento: " . ($forceUpdate ? 'SI' : 'NO') . "\n";
if ($limit) echo "Limite ricette: $limit\n";

// Seleziona le ricette (con limite opzionale)
$query = 'SELECT id, NER FROM recipes';
if ($limit) {
    $query = $query . ' LIMIT ' . $limit;
}
$stmt = $db->query($query);

// Contatori per statistiche
$calls = 0;
$i = 0;
$newIngredients = 0;
$updatedIngredients = 0;
$linked = 0;
echo "Inizio elaborazione...\n";



while ($recipe = $stmt->fetch()) {
    $i++;
    $recipeId = $recipe['id'];

    // Estrae l'array di ingredienti grezzi dal campo NER e li normalizza
    $names = $normalizer->fromNER($recipe['NER']);
    $ingredientIds = array();
    foreach ($names as $name) {
    	
        // Verifica se l'ingrediente esiste già nel database
        $check = $db->prepare('SELECT id 
        						FROM ingredients
        						WHERE name = ?');
        $check->execute(array($name));
        $existing = $check->fetch();

        if ($existing) {
            if ($forceUpdate) {
                $usda->getMacros($name);
                $updatedIngredients++;
                $calls++;
                usleep(1000000);
            }
            $ingredientIds[] = $existing['id'];
        } else {
            // Nuovo ingrediente -> chiama API
            $usda->getMacros($name);
            $newIngredients++;
            $calls++;
            usleep(1000000);

            // Recupera l'ID appena inserito
            $row = $db->prepare('SELECT id 
            					FROM ingredients 
            					WHERE name = ?');
            $row->execute(array($name));
            $ing = $row->fetch();
            if ($ing) {
                $ingredientIds[] = $ing['id'];
            }
        }
    }
    $numIngredients = count($ingredientIds);
    if ($numIngredients > 0) {
    	
        // Distribuisce 100g tot tra tutti gli ingredienti della ricetta
        $quantityPerIngredient = round(100 / $numIngredients, 1);
        foreach ($ingredientIds as $ingId) {
            $link = $db->prepare('INSERT IGNORE INTO recipe_ingredients (recipe_id,
            						ingredient_id, quantity_g) 
            						VALUES (?, ?, ?)');
            $link->execute(array($recipeId, $ingId, $quantityPerIngredient));
            $linked++;
        }
    }

    // Ogni 5 ricette mostra un riepilogo
    if ($i % 5 === 0) {
        echo "Ricette: $i , Chiamate API: $calls , Nuovi: $newIngredients , Aggiornati: $updatedIngredients\n";
    }
}
echo "Completato\n";
echo "Ricette processate: $i\n";
echo "Chiamate API: $calls\n";
echo "Nuovi ingredienti: $newIngredients\n";
echo "Aggiornati (forzati): $updatedIngredients\n";
echo "Collegamenti recipe_ingredients: $linked\n";