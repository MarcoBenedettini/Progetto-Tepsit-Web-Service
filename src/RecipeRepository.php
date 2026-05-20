<?php
require_once __DIR__ . '/Database.php';

class RecipeRepository {
    private PDO $db;
    public function __construct() {
        $this->db = Database::get();
    }

    public function getCandidates(string $mealType, string $diet, array $allergies, array $usedIds): array {
        $params = [$mealType];
        $conditions = ["r.meal_types = ?"];

        foreach ($allergies as $allergen) {
            $conditions[] = "r.id NOT IN (
                SELECT ri.recipe_id
                FROM recipe_ingredients ri
                JOIN ingredients i ON i.id = ri.ingredient_id
                WHERE i.name LIKE ?
            )";
            $params[] = "%$allergen%";
        }

        if (!empty($usedIds)) {
            $placeholders = implode(',', array_fill(0, count($usedIds), '?'));
            $conditions[] = "r.id NOT IN ($placeholders)";
            $params = array_merge($params, $usedIds);
        }

        $where = "WHERE " . implode(" AND ", $conditions);

        // Prima prova: range ampio (100–1200 kcal)
        $sql = "
            SELECT 
                r.id, r.title,
                ROUND(SUM(ri.quantity_g * i.kcal_per_100g / 100), 1) AS total_kcal,
                ROUND(SUM(ri.quantity_g * i.protein_g    / 100), 1) AS total_protein,
                ROUND(SUM(ri.quantity_g * i.fat_g        / 100), 1) AS total_fat,
                ROUND(SUM(ri.quantity_g * i.carbs_g      / 100), 1) AS total_carbs,
                ROUND(SUM(ri.quantity_g * i.fiber_g      / 100), 1) AS total_fiber,
                ROUND(SUM(ri.quantity_g * i.sugar_g      / 100), 1) AS total_sugar,
                ROUND(SUM(ri.quantity_g * i.saturated_fat_g / 100), 1) AS total_saturated_fat
            FROM recipes r
            JOIN recipe_ingredients ri ON ri.recipe_id = r.id
            JOIN ingredients i ON i.id = ri.ingredient_id
            $where
            GROUP BY r.id
            HAVING total_kcal BETWEEN 100 AND 1200 AND total_protein > 5
            ORDER BY RAND()
            LIMIT 50
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();

        if (empty($results)) {
            // Fallback: range ancora più ampio (50–1200)
            $sql = "
                SELECT 
                    r.id, r.title,
                    ROUND(SUM(ri.quantity_g * i.kcal_per_100g / 100), 1) AS total_kcal,
                    ROUND(SUM(ri.quantity_g * i.protein_g    / 100), 1) AS total_protein,
                    ROUND(SUM(ri.quantity_g * i.fat_g        / 100), 1) AS total_fat,
                    ROUND(SUM(ri.quantity_g * i.carbs_g      / 100), 1) AS total_carbs,
                    ROUND(SUM(ri.quantity_g * i.fiber_g      / 100), 1) AS total_fiber,
                    ROUND(SUM(ri.quantity_g * i.sugar_g      / 100), 1) AS total_sugar,
                    ROUND(SUM(ri.quantity_g * i.saturated_fat_g / 100), 1) AS total_saturated_fat
                FROM recipes r
                JOIN recipe_ingredients ri ON ri.recipe_id = r.id
                JOIN ingredients i ON i.id = ri.ingredient_id
                $where
                GROUP BY r.id
                HAVING total_kcal BETWEEN 50 AND 1200
                ORDER BY RAND()
                LIMIT 50
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll();
        }

        return $results;
    }

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