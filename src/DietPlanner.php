<?php

require_once __DIR__ . '/RecipeRepository.php';

class DietPlanner {

    private RecipeRepository $repo;

    private array $usedIds         = [];
    private array $usedIngredients = [];

    private const MEALS = [
        'breakfast' => 0.25,
        'lunch'     => 0.40,
        'dinner'    => 0.35,
    ];

    // Etichette italiane per il frontend
    private const MEAL_LABELS = [
        'breakfast' => 'Colazione',
        'lunch'     => 'Pranzo',
        'dinner'    => 'Cena',
    ];

    public function __construct() {
        $this->repo = new RecipeRepository();
    }

    public function build(array $params): array {

        $plan          = [];
        $totalCalories = 0.0;
        $allIngredients = [];

        for ($day = 1; $day <= $params['days']; $day++) {

            $dayMeals    = [];
            $dayCalories = 0.0;
            $dayProtein  = 0.0;

            foreach (self::MEALS as $mealType => $quota) {

                $targetKcal = $params['calories'] * $quota;

                $candidates = $this->repo->getCandidates(
                    $mealType,
                    $params['diet'],
                    $params['allergies'],
                    $this->usedIds
                );

                $picked = $this->pick($candidates);

                if ($picked === null) {
                    $dayMeals[] = [
                        'meal'  => self::MEAL_LABELS[$mealType],
                        'error' => 'Nessuna ricetta disponibile',
                    ];
                    continue;
                }

                $factor = $picked['total_kcal'] > 0
                    ? max(0.5, min(2.0, round($targetKcal / $picked['total_kcal'], 2)))
                    : 1.0;

                $calories = round($picked['total_kcal']    * $factor, 1);
                $protein  = round($picked['total_protein'] * $factor, 1);

                $this->usedIds[] = (int)$picked['id'];

                $recipeIngredients = $this->repo->getIngredients((int)$picked['id']);
                foreach ($recipeIngredients as $ing) {
                    $this->usedIngredients[] = $ing['name'];
                    $allIngredients[]        = $ing['name'];
                }

                $dayMeals[] = [
                    'meal'           => self::MEAL_LABELS[$mealType],
                    'recipe'         => $picked['title'],
                    'recipe_id'      => (int)$picked['id'],
                    'portion_factor' => $factor,
                    'calories'       => $calories,
                    'protein_g'      => $protein,
                    'cost_eur'       => 0.0,
                ];

                $dayCalories += $calories;
                $dayProtein  += $protein;
            }

            $totalCalories += $dayCalories;

            $plan[] = [
                'day'   => $day,
                'meals' => $dayMeals,
                'totals' => [
                    'calories'  => round($dayCalories, 1),
                    'protein_g' => round($dayProtein, 1),
                    'cost_eur'  => 0.0,
                ],
            ];
        }

        $summary = [
            'days'                 => $params['days'],
            'unique_recipes_used'  => count($this->usedIds),
            'unique_ingredients'   => count(array_unique($allIngredients)),
            'avg_calories_per_day' => $params['days'] > 0
                ? round($totalCalories / $params['days'], 1)
                : 0,
            'total_cost_eur'       => 0.0,
        ];

        return [$plan, $summary];
    }

    private function pick(array $candidates): ?array {

        foreach ($candidates as $recipe) {
            $names = array_column(
                $this->repo->getIngredients((int)$recipe['id']),
                'name'
            );
            if (empty(array_intersect($names, $this->usedIngredients))) {
                return $recipe;
            }
        }

        return $candidates[0] ?? null;
    }
}
