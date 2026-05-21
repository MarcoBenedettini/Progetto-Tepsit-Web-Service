<?php
require_once __DIR__ . '/Database.php';

// Classe per interagire con le tabelle recipes, recipe_ingredients e ingredients
class RecipeRepository {
    private $db;
    public function __construct() {
        $this->db = Database::get();
    }


    // Restituisce un array di ricette candidate filtrate
    public function getCandidates($mealType, $diet, $allergies, $usedIds) {
        // tipo di pasto
        if ($mealType === 'snack') {
    		$mealTypes = array('snack', 'other');
		} else {
    		$mealTypes = array($mealType);
		}
        // Crea i segnaposto per la query (tanti '?' quanti sono i mealTypes)
        $placeholders = implode(',', array_fill(0, count($mealTypes), '?'));	// implode -> unisce gli elementi di un array in una stringa
        																		// separandoli con un carattere scelto.
        																		
    	$conditions = array("r.meal_types IN ($placeholders)");	// controllo tipo di pasto della ricetta è tra quelli cercati
        $params = $mealTypes;

      
      
      
        // Per ogni allergia esclude le ricette che contengono ingredienti con quel nome
        foreach ($allergies as $allergen) {
            $conditions[] = "r.id NOT IN (
            	SELECT ri.recipe_id
                FROM recipe_ingredients ri
                JOIN ingredients i ON i.id = ri.ingredient_id
                WHERE i.name LIKE ?
            )";
            $params[] = "%$allergen%";
        }

        // Ricette gia usate
        if (!empty($usedIds)) {
        	
            // Crea una stringa con tanti '?' quanti sono gli ID in usedIds
            $placeholders = implode(',', array_fill(0, count($usedIds), '?'));
            $conditions[] = "r.id NOT IN ($placeholders)";
            
            
            // Aggiunge tutti gli ID alla lista dei parametri
            $params = array_merge($params, $usedIds);
        }
        $where = "WHERE " . implode(" AND ", $conditions);


        // Query per la selezione di 50 ricette casuali che 
        // rispettano i filtri e i requisiti (mantiene la risposta veloce)
        $sql = "SELECT r.id, r.title, r.instructions,
            ROUND(SUM(ri.quantity_g * i.kcal_per_100g / 100), 1) AS total_kcal,
            ROUND(SUM(ri.quantity_g * i.protein_g / 100), 1) AS total_protein,
            ROUND(SUM(ri.quantity_g * i.fat_g / 100), 1) AS total_fat,
            ROUND(SUM(ri.quantity_g * i.carbs_g / 100), 1) AS total_carbs,
            ROUND(SUM(ri.quantity_g * i.fiber_g / 100), 1) AS total_fiber,
            ROUND(SUM(ri.quantity_g * i.sugar_g / 100), 1) AS total_sugar,
            ROUND(SUM(ri.quantity_g * i.saturated_fat_g / 100), 1) AS total_saturated_fat
            FROM recipes r
            JOIN recipe_ingredients ri ON ri.recipe_id = r.id
            JOIN ingredients i ON i.id = ri.ingredient_id
            $where GROUP BY r.id
            HAVING total_kcal BETWEEN 100 AND 1200 AND total_protein > 5
            ORDER BY RAND()
            LIMIT 50";

        $stmt = $this->db->prepare($sql);	// Prepara query
        $stmt->execute($params);	// Esegue con i parametri
        $results = $stmt->fetchAll();	// Recupera tutti i risultati


		// Query di riserva con range più ampio (50 a 1200 kcal)
        if (empty($results)) {
            $sql = "SELECT r.id, r.title, r.instructions,
                ROUND(SUM(ri.quantity_g * i.kcal_per_100g / 100), 1) AS total_kcal,
                ROUND(SUM(ri.quantity_g * i.protein_g / 100), 1) AS total_protein,
                ROUND(SUM(ri.quantity_g * i.fat_g / 100), 1) AS total_fat,
                ROUND(SUM(ri.quantity_g * i.carbs_g / 100), 1) AS total_carbs,
                ROUND(SUM(ri.quantity_g * i.fiber_g / 100), 1) AS total_fiber,
                ROUND(SUM(ri.quantity_g * i.sugar_g / 100), 1) AS total_sugar,
				ROUND(SUM(ri.quantity_g * i.saturated_fat_g / 100), 1) AS total_saturated_fat
                FROM recipes r
                JOIN recipe_ingredients ri ON ri.recipe_id = r.id
                JOIN ingredients i ON i.id = ri.ingredient_id
                $where GROUP BY r.id
                HAVING total_kcal BETWEEN 50 AND 1200
                ORDER BY RAND()
                LIMIT 50";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll();
        }
        return $results;
    }


	// Elenco degli ingredienti per una data ricetta con le proprie quantita
	public function getIngredients($recipeId) {
        $stmt = $this->db->prepare("SELECT i.name, ri.quantity_g
            FROM recipe_ingredients ri
            JOIN ingredients i ON i.id = ri.ingredient_id
            WHERE ri.recipe_id = ?");	// '?' -> $recipeId
        $stmt->execute(array($recipeId));
        return $stmt->fetchAll();
    }
}