<?php
require_once __DIR__ . '/../config.php';	// Costrutto che serve per includere 
											// un file all'interno di un altro script
require_once __DIR__ . '/Database.php';

// Si interroga l'API per ottenere i valori nutrizionali
class UsdaClient {
    private $db;  // Connessione PDO al database
    public function __construct() {
        $this->db = Database::get();
    }

    
    // Ottiene i macro e micro nutrienti per 100g di un ingrediente
    
    // Cerca nella tabella ingredients, se non trova chiama la API,
    // senno usa una stima basata su parole chiave

    public function getMacros($name) {
        // ingredients
        $stmt = $this->db->prepare('SELECT kcal_per_100g,
									protein_g, fat_g, carbs_g, 
									fiber_g, sugar_g, saturated_fat_g 
            FROM ingredients
            WHERE name = ?');
        $stmt->execute(array($name));
        $cached = $stmt->fetch();

        // Se ho trovato restituisco subito i valori
        if ($cached) {
            return $cached;
        }

        // Non ho trovato..
        $queryParams = array('query' => $name,
            'api_key' => USDA_API_KEY,
            'pageSize' => 1,
            'dataType' => 'Foundation Food,SR Legacy');
        $url = USDA_URL . '?' . http_build_query($queryParams);	// Array in stringa di parametri URL
        
        $json = @file_get_contents($url);	// Scarica contenuto URL
        
        $data = null;
		if ($json) {
    		$data = json_decode($json, true);	// Converte stringa JSON in array PHP
		}
        $macros = null;
        $source = 'estimated';

        // Se la risposta contiene nutrienti li estrae
        if (isset($data['foods'][0]['foodNutrients']) && count($data['foods'][0]['foodNutrients']) > 0) {
    		$macros = $this->extractNutrients($data['foods'][0]['foodNutrients']);
    		$source = 'usda';
		}

        // Utilizzo della stima se API non restituisce valori
        if (!$macros) {
            $macros = $this->estimate($name);
        }

        // Salva in ingredients (inserisce o aggiorna la riga)
        $ins = $this->db->prepare('INSERT INTO ingredients (name, kcal_per_100g, 
        							protein_g, fat_g, carbs_g, fiber_g, sugar_g, 
        							saturated_fat_g, source)
            			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            			ON DUPLICATE KEY UPDATE fetched_at = CURRENT_TIMESTAMP');
            
        $ins->execute(array($name,
            $macros['kcal_per_100g'],
            $macros['protein_g'],
            $macros['fat_g'],
            $macros['carbs_g'],
            $macros['fiber_g'],
            $macros['sugar_g'],
            $macros['saturated_fat_g'], $source
            ));
        return $macros;
    }

    // Estrae nutrienti dalla risposta API
    private function extractNutrients($nutrients) {
        $out = array('kcal_per_100g' => 0,
            'protein_g' => 0,
            'fat_g' => 0,
            'carbs_g' => 0,
            'fiber_g' => 0,
            'sugar_g' => 0,
            'saturated_fat_g' => 0);

        foreach ($nutrients as $n) {
    		$nutrientId = 0;
    		if (isset($n['nutrientId'])) {
        		$nutrientId = $n['nutrientId'];
    		}
			$value = 0;
    		if (isset($n['value'])) {
        		$value = $n['value'];
    		}

    		// ID nutrienti
    		switch ($nutrientId) {
        		case 1008:
            		$out['kcal_per_100g'] = $value;
            		break;
            
        		case 1003:
        			$out['protein_g'] = $value;
            		break;
            
        		case 1004:
            		$out['fat_g'] = $value;
            		break;
            
        		case 1005:
            		$out['carbs_g'] = $value;
            		break;
            
        		case 1079:
            		$out['fiber_g'] = $value;
            		break;
            
        		case 2000:
            		$out['sugar_g'] = $value;
            		break;
            
        		case 1258:
            		$out['saturated_fat_g'] = $value;
            		break;
            
        		default:
            		break;
    		}
		}
        return $out;
    }



    // Stima valori nutrizionali in base a parole chiave nell'ingrediente
    private function estimate($name) {
        // Carne e pesce
        if (preg_match('/chicken|beef|pork|fish|salmon|tuna|meat|turkey/', $name)) {
            return array('kcal_per_100g' => 190,
                'protein_g' => 22,
                'fat_g' => 9,
                'carbs_g' => 0,
                'fiber_g' => 0,
                'sugar_g' => 0,
                'saturated_fat_g' => 3);
        }
        
        // Cereali e farinacei
        if (preg_match('/rice|pasta|bread|flour|oat|wheat/', $name)) {
            return array('kcal_per_100g' => 355,
                'protein_g' => 7,
                'fat_g' => 1,
                'carbs_g' => 74,
                'fiber_g' => 3,
                'sugar_g' => 1,
                'saturated_fat_g' => 0);
        }
        
        // Grassi e oli
        if (preg_match('/oil|butter|cream|lard/', $name)) {
            return array('kcal_per_100g' => 720,
                'protein_g' => 0,
                'fat_g' => 80,
                'carbs_g' => 0,
                'fiber_g' => 0,
                'sugar_g' => 0,
                'saturated_fat_g' => 20);
        }
        
        // Latticini
        if (preg_match('/milk|yogurt|cheese/', $name)) {
            return array('kcal_per_100g' => 100,
                'protein_g' => 6,
                'fat_g' => 5,
                'carbs_g' => 8,
                'fiber_g' => 0,
                'sugar_g' => 8,
                'saturated_fat_g' => 3);
        }
        
        // Zuccheri e dolcificanti
        if (preg_match('/sugar|honey|syrup/', $name)) {
            return array('kcal_per_100g' => 310,
                'protein_g' => 0,
                'fat_g' => 0,
                'carbs_g' => 80,
                'fiber_g' => 0,
                'sugar_g' => 80,
                'saturated_fat_g' => 0);
        }
        
        // Valore generico per verdure e altro
        return array('kcal_per_100g' => 35,
            'protein_g' => 2,
            'fat_g' => 0,
            'carbs_g' => 6,
            'fiber_g' => 1,
            'sugar_g' => 2,
            'saturated_fat_g' => 0);
    }
}
