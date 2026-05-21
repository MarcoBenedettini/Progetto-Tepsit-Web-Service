<?php
require_once __DIR__ . '/Database.php';

class UsdaClient {
    private $db;

    public function __construct() {
        $this->db = Database::get();
    }

    public function getMacros($name) {
        // Controlla cache nel database
        $stmt = $this->db->prepare('SELECT kcal_per_100g, protein_g, fat_g, carbs_g FROM ingredients WHERE name = ?');
        $stmt->execute(array($name));
        $cached = $stmt->fetch();
        if ($cached) {
            return $cached;
        }

        // Chiamata API
        $url = USDA_URL . '?' . http_build_query(array(
            'query' => $name,
            'api_key' => USDA_API_KEY,
            'pageSize' => 1,
            'dataType' => 'Foundation Food,SR Legacy'
        ));
        $json = @file_get_contents($url);
        if ($json) {
            $data = json_decode($json, true);
        } else {
            $data = null;
        }
        $macros = null;
        $source = 'estimated';

        if (!empty($data['foods'][0]['foodNutrients'])) {
            $macros = $this->extractNutrients($data['foods'][0]['foodNutrients']);
            $source = 'usda';
        }

        // Stima per categoria se USDA non ha dato risultati
        if (!$macros) {
            $macros = $this->estimate($name);
        }

        // Salva in cache (INSERT OR IGNORE)
        $ins = $this->db->prepare(
            'INSERT OR IGNORE INTO ingredients (name, kcal_per_100g, protein_g, fat_g, carbs_g, source)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $ins->execute(array($name, $macros['kcal_per_100g'], $macros['protein_g'], $macros['fat_g'], $macros['carbs_g'], $source));
        return $macros;
    }

    private function extractNutrients($nutrients) {
        $out = array('kcal_per_100g' => 0, 'protein_g' => 0, 'fat_g' => 0, 'carbs_g' => 0);
        foreach ($nutrients as $n) {
            if (isset($n['nutrientId'])) {
                $nutrientId = $n['nutrientId'];
            } else {
                $nutrientId = 0;
            }
            if (isset($n['value'])) {
                $value = $n['value'];
            } else {
                $value = 0;
            }
            if ($nutrientId == 1008) {
                $out['kcal_per_100g'] = $value;
            } else if ($nutrientId == 1003) {
                $out['protein_g'] = $value;
            } else if ($nutrientId == 1004) {
                $out['fat_g'] = $value;
            } else if ($nutrientId == 1005) {
                $out['carbs_g'] = $value;
            }
        }
        return $out;
    }

    private function estimate($name) {
        if (preg_match('/chicken|beef|pork|fish|salmon|tuna|meat|turkey/', $name)) {
            return array('kcal_per_100g' => 190, 'protein_g' => 22, 'fat_g' => 9, 'carbs_g' => 0);
        }
        if (preg_match('/rice|pasta|bread|flour|oat|wheat/', $name)) {
            return array('kcal_per_100g' => 355, 'protein_g' => 7, 'fat_g' => 1, 'carbs_g' => 74);
        }
        if (preg_match('/oil|butter|cream|lard/', $name)) {
            return array('kcal_per_100g' => 720, 'protein_g' => 0, 'fat_g' => 80, 'carbs_g' => 0);
        }
        if (preg_match('/milk|yogurt|cheese/', $name)) {
            return array('kcal_per_100g' => 100, 'protein_g' => 6, 'fat_g' => 5, 'carbs_g' => 8);
        }
        if (preg_match('/sugar|honey|syrup/', $name)) {
            return array('kcal_per_100g' => 310, 'protein_g' => 0, 'fat_g' => 0, 'carbs_g' => 80);
        }
        // Default: verdura/frutta generica
        return array('kcal_per_100g' => 35, 'protein_g' => 2, 'fat_g' => 0, 'carbs_g' => 6);
    }
}