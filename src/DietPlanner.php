<?php
require_once __DIR__ . '/RecipeRepository.php';

class DietPlanner {
    private RecipeRepository $repo;
    private array $usedIds = [];
    private array $usedIngredients = [];

    private const MEALS = [
        'breakfast' => 0.25,
        'lunch'     => 0.40,
        'dinner'    => 0.35,
    ];

    private const MEALS_WITH_SNACKS = [
        'breakfast' => 0.20,
        'snack'     => 0.10,
        'lunch'     => 0.35,
        'dinner'    => 0.35,
    ];

    public function __construct() {
        $this->repo = new RecipeRepository();
    }

    public function build(array $params): array {
        $includeSnacks = $params['snacks'] ?? false;
        $mealScheme = $includeSnacks ? self::MEALS_WITH_SNACKS : self::MEALS;
        $plan = [];
        $totalCalories = 0.0;

        for ($day = 1; $day <= $params['days']; $day++) {
            $dayMeals = [];
            $dayCaloriesRaw = 0.0;
            $dayProteinRaw = 0.0;
            $dayFatRaw = 0.0;
            $dayCarbsRaw = 0.0;
            $dayFiberRaw = 0.0;
            $daySugarRaw = 0.0;
            $daySatFatRaw = 0.0;

            // Step 1: scegli i pasti con porzioni base (min 0.5, max 2.5)
            foreach ($mealScheme as $mealType => $quota) {
                $targetKcal = $params['calories'] * $quota;
                $found = false;

                for ($attempt = 0; $attempt < 5; $attempt++) {
                    $candidates = $this->repo->getCandidates(
                        $mealType,
                        $params['diet'],
                        $params['allergies'],
                        $this->usedIds
                    );

                    $picked = $this->pick($candidates);
                    if ($picked === null) break;

                    // Fattore base: 0.5 – 2.5
                    $factor = $picked['total_kcal'] > 0
                        ? max(0.5, min(2.5, round($targetKcal / $picked['total_kcal'], 2)))
                        : 1.0;

                    $calories = round($picked['total_kcal'] * $factor, 1);

                    // Se anche a porzione 0.5 supera troppo il target, scarta
                    if ($factor <= 0.51 && $calories > $targetKcal * 1.2) {
                        $this->usedIds[] = (int)$picked['id'];
                        continue;
                    }

                    $protein = round($picked['total_protein'] * $factor, 1);
                    $fat     = round($picked['total_fat'] * $factor, 1);
                    $carbs   = round($picked['total_carbs'] * $factor, 1);
                    $fiber   = round($picked['total_fiber'] * $factor, 1);
                    $sugar   = round($picked['total_sugar'] * $factor, 1);
                    $satFat  = round($picked['total_saturated_fat'] * $factor, 1);

                    $this->usedIds[] = (int)$picked['id'];
                    foreach ($this->repo->getIngredients((int)$picked['id']) as $ing) {
                        $this->usedIngredients[] = $ing['name'];
                    }

                    $dayMeals[] = [
                        'meal'           => $mealType,
                        'recipe'         => $picked['title'],
                        'recipe_id'      => (int)$picked['id'],
                        'portion_factor_base' => $factor,
                        'calories_base'  => $calories,
                        'protein_g_base' => $protein,
                        'fat_g_base'     => $fat,
                        'carbs_g_base'   => $carbs,
                        'fiber_g_base'   => $fiber,
                        'sugar_g_base'   => $sugar,
                        'saturated_fat_g_base' => $satFat,
                    ];

                    $dayCaloriesRaw += $calories;
                    $dayProteinRaw  += $protein;
                    $dayFatRaw      += $fat;
                    $dayCarbsRaw    += $carbs;
                    $dayFiberRaw    += $fiber;
                    $daySugarRaw    += $sugar;
                    $daySatFatRaw   += $satFat;

                    $found = true;
                    break;
                }

                if (!$found) {
                    $dayMeals[] = [
                        'meal'  => $mealType,
                        'error' => 'Nessuna ricetta valida trovata'
                    ];
                }
            }

            // Step 2: bilanciamento finale per centrare esattamente il target giornaliero
            $targetDay = $params['calories'];
            $adjustment = ($dayCaloriesRaw > 0) ? $targetDay / $dayCaloriesRaw : 1.0;
            // Limita l'aggiustamento tra 0.7 e 1.5 per evitare porzioni assurde
            $adjustment = max(0.7, min(1.5, $adjustment));

            $dayCalories = 0.0;
            $dayProtein = 0.0;
            $dayFat = 0.0;
            $dayCarbs = 0.0;
            $dayFiber = 0.0;
            $daySugar = 0.0;
            $daySatFat = 0.0;

            $finalMeals = [];
            foreach ($dayMeals as $meal) {
                if (isset($meal['error'])) {
                    $finalMeals[] = $meal;
                    continue;
                }

                $factorFinal = round($meal['portion_factor_base'] * $adjustment, 2);
                // Applica lo stesso aggiustamento a tutti i nutrienti
                $calories = round($meal['calories_base'] * $adjustment, 1);
                $protein  = round($meal['protein_g_base'] * $adjustment, 1);
                $fat      = round($meal['fat_g_base'] * $adjustment, 1);
                $carbs    = round($meal['carbs_g_base'] * $adjustment, 1);
                $fiber    = round($meal['fiber_g_base'] * $adjustment, 1);
                $sugar    = round($meal['sugar_g_base'] * $adjustment, 1);
                $satFat   = round($meal['saturated_fat_g_base'] * $adjustment, 1);

                $finalMeals[] = [
                    'meal'           => $meal['meal'],
                    'recipe'         => $meal['recipe'],
                    'recipe_id'      => $meal['recipe_id'],
                    'portion_factor' => $factorFinal,
                    'calories'       => $calories,
                    'protein_g'      => $protein,
                    'fat_g'          => $fat,
                    'carbs_g'        => $carbs,
                    'fiber_g'        => $fiber,
                    'sugar_g'        => $sugar,
                    'saturated_fat_g'=> $satFat,
                ];

                $dayCalories += $calories;
                $dayProtein  += $protein;
                $dayFat      += $fat;
                $dayCarbs    += $carbs;
                $dayFiber    += $fiber;
                $daySugar    += $sugar;
                $daySatFat   += $satFat;
            }

            $totalCalories += $dayCalories;
            $plan[] = [
                'day'   => $day,
                'meals' => $finalMeals,
                'totals' => [
                    'calories'  => round($dayCalories, 1),
                    'protein_g' => round($dayProtein, 1),
                    'fat_g'     => round($dayFat, 1),
                    'carbs_g'   => round($dayCarbs, 1),
                    'fiber_g'   => round($dayFiber, 1),
                    'sugar_g'   => round($daySugar, 1),
                    'saturated_fat_g' => round($daySatFat, 1),
                ],
            ];
        }

        $summary = [
            'days'                 => $params['days'],
            'unique_recipes_used'  => count($this->usedIds),
            'avg_calories_per_day' => $params['days'] > 0 ? round($totalCalories / $params['days'], 1) : 0,
        ];
        return [$plan, $summary];
    }

    private function pick(array $candidates): ?array {
        foreach ($candidates as $recipe) {
            $names = array_column($this->repo->getIngredients((int)$recipe['id']), 'name');
            if (empty(array_intersect($names, $this->usedIngredients))) {
                return $recipe;
            }
        }
        return $candidates[0] ?? null;
    }
}