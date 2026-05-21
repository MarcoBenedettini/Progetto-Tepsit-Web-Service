<?php

// Include la classe RecipeRepository
require_once __DIR__ . '/RecipeRepository.php';

class DietPlanner {
    // Repository per accedere alle ricette
    private $repo;

    // Array degli ID delle ricette già utilizzate per evitare ripetizioni
    private $usedIds = array();

    // Array dei nomi degli ingredienti già utilizzati per evitare similitudini
    private $usedIngredients = array();

    // Distribuzione calorica standard senza snack
    private $meals = array('breakfast' => 0.25, 'lunch' => 0.40, 'dinner' => 0.35);

    // Distribuzione calorica con snack
    private $mealsWithSnacks = array('breakfast' => 0.20, 'snack' => 0.10, 'lunch' => 0.35, 'dinner' => 0.35);


	// inizializza il repository delle ricette
    public function __construct() {
        $this->repo = new RecipeRepository();
    }


    public function build($params) {
        // Decide se includere lo snack (true/false)
        $includeSnacks = isset($params['snacks']) && $params['snacks'] == 1;

        // Sceglie la distribuzione calorica appropriata
        if ($includeSnacks) {
            $mealScheme = $this->mealsWithSnacks;
        } else {
            $mealScheme = $this->meals;
        }

		
		 // Array che conterrà tutti i giorni
        $plan = array();	
        $totalCalories = 0.0;

        // Ciclo per ogni giorno richiesto
        for ($day = 1; $day <= $params['days']; $day++) {
            $dayMeals = array();	// Pasti del giorno corrente

            // Variabili per accumulare i valori "grezzi" (prima del bilanciamento)
            $dayCaloriesRaw = 0.0;
            $dayProteinRaw = 0.0;
            $dayFatRaw = 0.0;
            $dayCarbsRaw = 0.0;
            $dayFiberRaw = 0.0;
            $daySugarRaw = 0.0;
            $daySatFatRaw = 0.0;

            // selezione dei pasti con porzione base 0.5 a 2.5
            foreach ($mealScheme as $mealType => $quota) {
                // Calorie target per questo pasto (calorie giornaliere * quota)
                $targetKcal = $params['calories'] * $quota;
                $found = false;

                // Tentativi per trovare una ricetta valida (max 5 tentativi)
                for ($attempt = 0; $attempt < 5; $attempt++) {
                    // Ottiene le ricette candidate per questo pasto
                    $candidates = $this->repo->getCandidates($mealType, 'none', $params['allergies'],  $this->usedIds);	// dieta fissa senza restrizioni

                    // Sceglie la miglior ricetta tra le candidate (evitando ripetizioni)
                    $picked = $this->pick($candidates);

                    // Se non ci sono candidates esce
                    if ($picked === null) {
                        break;
                    }

                    // Calcola il fattore di porzione (min 0.5, max 2.5)
                    if ($picked['total_kcal'] > 0) {
                        $rawFactor = $targetKcal / $picked['total_kcal'];
                        
                        $rawFactor = round($rawFactor, 2);	// round arrotonda il numero a 2 cifre decimali
                        
                        $factor = max(0.5, min(2.5, $rawFactor));
                    } else {
                        $factor = 1.0;
                    }

                    // Calcola le calorie effettive di questo pasto
                    $calories = round($picked['total_kcal'] * $factor, 1);

                    // Se anche con porzione minima 0.5 la ricetta supera del 20% il target,
                    // si scarta e si prende un'altra ricetta
                    if ($factor <= 0.51 && $calories > $targetKcal * 1.2) {
    					$this->usedIds[] = $picked['id'];
    					continue;
                    }

                    // Calcola tutti gli altri nutrienti scalati con lo stesso fattore
                    $protein = round($picked['total_protein'] * $factor, 1);
                    $fat = round($picked['total_fat'] * $factor, 1);
                    $carbs = round($picked['total_carbs'] * $factor, 1);
                    $fiber = round($picked['total_fiber'] * $factor, 1);
                    $sugar = round($picked['total_sugar'] * $factor, 1);
                    $satFat = round($picked['total_saturated_fat'] * $factor, 1);
                    
                    $instructions = '';
					if (isset($picked['instructions'])) {
					    $instructions = $picked['instructions'];
					}

                    // Memorizza l'ID della ricetta come usata per evitare ripetizioni future
                    $this->usedIds[] = (int)$picked['id'];

                    // Memorizza tutti gli ingredienti della ricetta come usati
                    $ingredients = $this->repo->getIngredients((int)$picked['id']);
                    foreach ($ingredients as $ing) {
                        $this->usedIngredients[] = $ing['name'];
                    }

                    // Salva i dati del pasto (con valori "base" prima del bilanciamento)
                    $dayMeals[] = array(
    					'meal' => $mealType,
    					'recipe' => $picked['title'],
    					'instructions' => $instructions,
    					'recipe_id' => $picked['id'],
    					'portion_factor_base' => $factor,
    					'calories_base' => $calories,
    					'protein_g_base' => $protein,
    					'fat_g_base' => $fat,
    					'carbs_g_base' => $carbs,
    					'fiber_g_base' => $fiber,
    					'sugar_g_base' => $sugar,
    					'saturated_fat_g_base' => $satFat
    				);

                    // Aggiorna i totali "grezzi" del giorno
                    $dayCaloriesRaw += $calories;
                    $dayProteinRaw  += $protein;
                    $dayFatRaw += $fat;
                    $dayCarbsRaw += $carbs;
                    $dayFiberRaw += $fiber;
                    $daySugarRaw += $sugar;
                    $daySatFatRaw += $satFat;

                    $found = true;
                    break;  // Esce dal ciclo dei tentativi -> ricetta trovata
                }

                // Se dopo 5 tentativi non si è trovata una ricetta valida, aggiungi un errore
                if (!$found) {
                    $dayMeals[] = array('meal'  => $mealType, 'error' => 'Nessuna ricetta valida trovata');
                }
            }

            // bilanciamento finale per avere le calorie target esatte
            $targetDay = $params['calories'];

            // Calcola il coefficiente di aggiustamento = target / calorie ottenute grezze
            if ($dayCaloriesRaw > 0) {
                $adjustment = $targetDay / $dayCaloriesRaw;
            } else {
                $adjustment = 1.0;
            }

            // Limita il coefficiente tra 0.7 e 1.5 per evitare porzioni assurde
            $adjustment = max(0.7, min(1.5, $adjustment));

            // Variabili per i totali finali del giorno (dopo il bilanciamento)
            $dayCalories = 0.0;
            $dayProtein = 0.0;
            $dayFat = 0.0;
            $dayCarbs = 0.0;
            $dayFiber = 0.0;
            $daySugar = 0.0;
            $daySatFat = 0.0;

            $finalMeals = array();

            // Applica l'aggiustamento a tutti i pasti del giorno
            foreach ($dayMeals as $meal) {
                // Se il pasto contiene un errore lo copia così com'è
                if (isset($meal['error'])) {
                	// isset -> controlla se una variabile o un campo di un array esiste e non è null
                    
                    $finalMeals[] = $meal;
                    continue;
                }

                // Nuovo fattore di porzione = fattore_base * adjustment
                $factorFinal = round($meal['portion_factor_base'] * $adjustment, 2);

                // Applica lo stesso adjustment a tutti i nutrienti
                $calories = round($meal['calories_base'] * $adjustment, 1);
                $protein  = round($meal['protein_g_base'] * $adjustment, 1);
                $fat = round($meal['fat_g_base'] * $adjustment, 1);
                $carbs = round($meal['carbs_g_base'] * $adjustment, 1);
                $fiber = round($meal['fiber_g_base'] * $adjustment, 1);
                $sugar = round($meal['sugar_g_base'] * $adjustment, 1);
                $satFat = round($meal['saturated_fat_g_base'] * $adjustment, 1);

                // Costruzione il pasto finale
                $finalMeals[] = array(
                    'meal' => $meal['meal'],
                    'recipe' => $meal['recipe'],
                    'instructions' => $meal['instructions'],
                    'recipe_id' => $meal['recipe_id'],
                    'portion_factor' => $factorFinal,
                    'calories' => $calories,
                    'protein_g' => $protein,
                    'fat_g' => $fat,
                    'carbs_g' => $carbs,
                    'fiber_g' => $fiber,
                    'sugar_g' => $sugar,
                    'saturated_fat_g'=> $satFat
                );

                // Aggiorna i totali finali
                $dayCalories += $calories;
                $dayProtein  += $protein;
                $dayFat += $fat;
                $dayCarbs += $carbs;
                $dayFiber += $fiber;
                $daySugar += $sugar;
                $daySatFat += $satFat;
            }

            // Aggiunge la giornata al piano
            $totalCalories += $dayCalories;
            $plan[] = array(
                'day' => $day,
                'meals' => $finalMeals,
                'totals' => array('calories' => round($dayCalories, 1),
                    'protein_g' => round($dayProtein, 1),
                    'fat_g' => round($dayFat, 1),
                    'carbs_g' => round($dayCarbs, 1),
                    'fiber_g' => round($dayFiber, 1),
                    'sugar_g' => round($daySugar, 1),
                    'saturated_fat_g' => round($daySatFat, 1))
            );
        }

        // Costruzione riepilogo finale
        $summary = array('days' => $params['days'], 'unique_recipes_used' => count($this->usedIds), 'avg_calories_per_day' => 0);
        if ($params['days'] > 0) {
            $summary['avg_calories_per_day'] = round($totalCalories / $params['days'], 1);
        }

        // Restituisce il piano e il riepilogo
        return array($plan, $summary);
    }


    //Se tutte le candidate usano ingredienti già usati, restituisce la prima.
    private function pick($candidates) {
    	
        // Cerca una ricetta che non abbia ingredienti già utilizzati
        foreach ($candidates as $recipe) {
        	
            // Ottiene i nomi degli ingredienti della ricetta
            $ingredients = $this->repo->getIngredients($recipe['id']);
            $names = array();
            
            foreach ($ingredients as $ing) {
                $names[] = $ing['name'];
            }

            // Calcola l'intersezione tra ingredienti della ricetta e quelli già usati
            $intersection = array_intersect($names, $this->usedIngredients);

            // Se l'intersezione è vuota, la ricetta è valida
            if (empty($intersection)) {
                return $recipe;
            }
        }

        // Se nessuna ricetta è pulita, restituisce la prima candidata (o null se vuoto)
        if (count($candidates) > 0) {
            return $candidates[0];
        } else {
            return null;
        }
    }
}