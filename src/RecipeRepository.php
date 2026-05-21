<?php

require_once __DIR__ . '/Database.php';

class RecipeRepository {
    private $db;

    public function __construct() {
        $this->db = Database::get();
    }

    public function getCandidates($mealType, $allergies, $usedIds) {
        $params     = array("%$mealType%");
        $conditions = array("r.meal_types LIKE ?");

        // Filtro per allergie escludi ricette che contengono l'allergene
        foreach ($allergies as $allergen) {
            $conditions[] = "r.id NOT IN (
                SELECT ri.recipe_id
                FROM recipe_ingredients ri
                JOIN ingredients i ON i.id = ri.ingredient_id
                WHERE i.name LIKE ?
            )";
            $params[] = "%$allergen%";
        }

        // Escludi ricette già usate
        if (!empty($usedIds)) {
            $placeholders = implode(',', array_fill(0, count($usedIds), '?'));
            $conditions[] = "r.id NOT IN ($placeholders)";
            $params = array_merge($params, $usedIds);
        }

        $where = "WHERE " . implode(" AND ", $conditions);

        // Query per calcolare kcal e proteine totali
        $sql = "
            SELECT
                r.id,
                r.title,
                ROUND(SUM(ri.quantity_g * i.kcal_per_100g / 100), 1) AS total_kcal,
                ROUND(SUM(ri.quantity_g * i.protein_g    / 100), 1) AS total_protein
            FROM recipes r
            JOIN recipe_ingredients ri ON ri.recipe_id = r.id
            JOIN ingredients        i  ON i.id = ri.ingredient_id
            $where
            GROUP BY r.id
            HAVING total_kcal > 100 AND total_protein > 1
            ORDER BY RANDOM()
            LIMIT 30
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getIngredients($recipeId) {
        $stmt = $this->db->prepare("
            SELECT i.name, ri.quantity_g
            FROM recipe_ingredients ri
            JOIN ingredients i ON i.id = ri.ingredient_id
            WHERE ri.recipe_id = ?
        ");
        $stmt->execute(array($recipeId));
        return $stmt->fetchAll();
    }
}