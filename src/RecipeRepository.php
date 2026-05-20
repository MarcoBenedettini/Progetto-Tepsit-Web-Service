<?php

require_once __DIR__ . '/Database.php';

class RecipeRepository {

    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    /**
     * Restituisce ricette candidate per un pasto.
     * Filtra per meal_type, dieta, allergie e ricette già usate.
     */
    public function getCandidates(string $mealType, string $diet, array $allergies, array $usedIds): array {

        $params     = ["%$mealType%"];
        $conditions = ["r.meal_types LIKE ?"];

        // -----------------------------
        // FILTRI DIETA
        // -----------------------------
        switch ($diet) {
            case 'vegan':
                $conditions[] = "r.is_vegan = 1";
                break;
            case 'vegetarian':
                $conditions[] = "r.is_vegetarian = 1";
                break;
            case 'gluten_free':
                $conditions[] = "r.is_gluten_free = 1";
                break;
            case 'lactose_free':
                $conditions[] = "r.is_dairy_free = 1";
                break;
            case 'pescatarian':
                $conditions[] = "(r.is_vegetarian = 1 OR r.title LIKE '%fish%' OR r.title LIKE '%salmon%' OR r.title LIKE '%tuna%')";
                break;
            case 'none':
            default:
                break;
        }

        // -----------------------------
        // FILTRI ALLERGIE
        // -----------------------------
        foreach ($allergies as $allergen) {
            $conditions[] = "r.id NOT IN (
                SELECT ri.recipe_id
                FROM recipe_ingredients ri
                JOIN ingredients i ON i.id = ri.ingredient_id
                WHERE i.name LIKE ?
            )";
            $params[] = "%$allergen%";
        }

        // -----------------------------
        // ESCLUDI RICETTE GIÀ USATE
        // -----------------------------
        if (!empty($usedIds)) {
            $placeholders = implode(',', array_fill(0, count($usedIds), '?'));
            $conditions[] = "r.id NOT IN ($placeholders)";
            $params       = array_merge($params, $usedIds);
        }

        // -----------------------------
        // QUERY FINALE (SQLite: RANDOM())
        // -----------------------------
        $where = "WHERE " . implode(" AND ", $conditions);

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

    /**
     * Restituisce ingredienti di una ricetta.
     */
    public function getIngredients(int $recipeId): array {
        $stmt = $this->db->prepare("
            SELECT i.name, ri.quantity_g
            FROM recipe_ingredients ri
            JOIN ingredients i ON i.id = ri.ingredient_id
            WHERE ri.recipe_id = ?
        ");
        $stmt->execute([$recipeId]);
        return $stmt->fetchAll();
    }
}
