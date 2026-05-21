<?php

require_once __DIR__ . '/RecipeRepository.php';

class DietPlanner {

    private $repo;

    private $usedIds = array();
    private $usedIngredients = array();

    // Distribuzione delle calorie nei pasti principali
    private $meals = array(
        'breakfast' => 0.25,
        'lunch' => 0.40,
        'dinner' => 0.35,
    );

    // Etichette in italiano
    private $mealLabels = array(
        'breakfast' => 'Colazione',
        'lunch' => 'Pranzo',
        'dinner' => 'Cena',
    );

    public function __construct() {
        $this->repo = new RecipeRepository();
    }

    public function build($params) {
        $plan = array();
        $totalCalories = 0.0;
        $allIngredients = array();

        for ($day = 1; $day <= $params['days']; $day++) {
            $dayMeals = array();
            $dayCalories = 0.0;
            $dayProtein = 0.0;

            foreach ($this->meals as $mealType => $quota) {
                $targetKcal = $params['calories'] * $quota;

                // Ottiene i candidati filtrati per tipo pasto e allergie
                $candidates = $this->repo->getCandidates(
                    $mealType,
                    $params['allergies'],
                    $this->usedIds
                );

                $picked = $this->pick($candidates);

                if ($picked === null) {
                    $dayMeals[] = array(
                        'meal' => $this->mealLabels[$mealType],
                        'error' => 'Nessuna ricetta disponibile',
                    );
                    continue;
                }

                // Calcola il fattore di porzione per avvicinarsi al target calorico
                if ($picked['total_kcal'] > 0) {
                    $raw = $targetKcal / $picked['total_kcal'];
                    $raw = round($raw, 2);
                    if ($raw < 0.5) {
                        $factor = 0.5;
                    } else if ($raw > 2.0) {
                        $factor = 2.0;
                    } else {
                        $factor = $raw;
                    }
                } else {
                    $factor = 1.0;
                }

                $calories = round($picked['total_kcal'] * $factor, 1);
                $protein = round($picked['total_protein'] * $factor, 1);

                $this->usedIds[] = $picked['id'];

                // Registra ingredienti usati
                $recipeIngredients = $this->repo->getIngredients($picked['id']);
                foreach ($recipeIngredients as $ing) {
                    $this->usedIngredients[] = $ing['name'];
                    $allIngredients[] = $ing['name'];
                }

                $dayMeals[] = array(
                    'meal' => $this->mealLabels[$mealType],
                    'recipe' => $picked['title'],
                    'recipe_id' => $picked['id'],
                    'portion_factor' => $factor,
                    'calories' => $calories,
                    'protein_g' => $protein,
                );

                $dayCalories = $dayCalories + $calories;
                $dayProtein = $dayProtein + $protein;
            }

            $totalCalories = $totalCalories + $dayCalories;

            $plan[] = array(
                'day' => $day,
                'meals' => $dayMeals,
                'totals' => array(
                    'calories' => round($dayCalories, 1),
                    'protein_g' => round($dayProtein, 1),
                ),
            );
        }

        if ($params['days'] > 0) {
            $avgCalories = round($totalCalories / $params['days'], 1);
        } else {
            $avgCalories = 0;
        }

        $summary = array(
            'days' => $params['days'],
            'unique_recipes_used' => count($this->usedIds),
            'unique_ingredients' => count(array_unique($allIngredients)),
            'avg_calories_per_day' => $avgCalories,
        );

        return array($plan, $summary);
    }

    private function pick($candidates) {
        foreach ($candidates as $recipe) {
            $names = array_column(
                $this->repo->getIngredients($recipe['id']),
                'name'
            );
            if (empty(array_intersect($names, $this->usedIngredients))) {
                return $recipe;
            }
        }

        if (isset($candidates[0])) {
            return $candidates[0];
        } else {
            return null;
        }
    }
}