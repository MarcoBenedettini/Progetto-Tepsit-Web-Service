<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/Database.php';

class UsdaClient {

    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    public function getMacros(string $name): array {
        $stmt = $this->db->prepare('SELECT kcal_per_100g, protein_g, fat_g, carbs_g, fiber_g, sugar_g, saturated_fat_g FROM ingredients WHERE name = ?');
        $stmt->execute([$name]);
        $cached = $stmt->fetch();
        if ($cached) return $cached;

        $url    = USDA_URL . '?' . http_build_query(['query' => $name, 'api_key' => USDA_API_KEY, 'pageSize' => 1, 'dataType' => 'Foundation Food,SR Legacy']);
        $json   = @file_get_contents($url);
        $data   = $json ? json_decode($json, true) : null;
        $macros = null;
        $source = 'estimated';

        if (!empty($data['foods'][0]['foodNutrients'])) {
            $macros = $this->extractNutrients($data['foods'][0]['foodNutrients']);
            $source = 'usda';
        }

        if (!$macros) {
            $macros = $this->estimate($name);
        }

        $ins = $this->db->prepare(
            'INSERT INTO ingredients 
            (name, kcal_per_100g, protein_g, fat_g, carbs_g, fiber_g, sugar_g, saturated_fat_g, source)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE fetched_at = fetched_at'
        );
        $ins->execute([
            $name,
            $macros['kcal_per_100g'],
            $macros['protein_g'],
            $macros['fat_g'],
            $macros['carbs_g'],
            $macros['fiber_g'],
            $macros['sugar_g'],
            $macros['saturated_fat_g'],
            $source
        ]);

        return $macros;
    }

    private function extractNutrients(array $nutrients): array {
        $out = [
            'kcal_per_100g' => 0,
            'protein_g' => 0,
            'fat_g' => 0,
            'carbs_g' => 0,
            'fiber_g' => 0,
            'sugar_g' => 0,
            'saturated_fat_g' => 0
        ];
        foreach ($nutrients as $n) {
            $nutrientId = (int)($n['nutrientId'] ?? 0);
            $value = (float)($n['value'] ?? 0);
            switch ($nutrientId) {
                case 1008: $out['kcal_per_100g'] = $value; break; // Energia
                case 1003: $out['protein_g'] = $value; break;
                case 1004: $out['fat_g'] = $value; break;
                case 1005: $out['carbs_g'] = $value; break;
                case 1079: $out['fiber_g'] = $value; break; // Fibra
                case 2000: $out['sugar_g'] = $value; break; // Zuccheri totali
                case 1258: $out['saturated_fat_g'] = $value; break; // Grassi saturi
                default: break;
            }
        }
        return $out;
    }

    private function estimate(string $name): array {
        // Stima di base (solo macro, senza i nuovi campi)
        if (preg_match('/chicken|beef|pork|fish|salmon|tuna|meat|turkey/', $name))
            return ['kcal_per_100g' => 190, 'protein_g' => 22, 'fat_g' => 9,  'carbs_g' => 0, 'fiber_g' => 0, 'sugar_g' => 0, 'saturated_fat_g' => 3];
        if (preg_match('/rice|pasta|bread|flour|oat|wheat/', $name))
            return ['kcal_per_100g' => 355, 'protein_g' => 7,  'fat_g' => 1,  'carbs_g' => 74, 'fiber_g' => 3, 'sugar_g' => 1, 'saturated_fat_g' => 0];
        if (preg_match('/oil|butter|cream|lard/', $name))
            return ['kcal_per_100g' => 720, 'protein_g' => 0,  'fat_g' => 80, 'carbs_g' => 0, 'fiber_g' => 0, 'sugar_g' => 0, 'saturated_fat_g' => 20];
        if (preg_match('/milk|yogurt|cheese/', $name))
            return ['kcal_per_100g' => 100, 'protein_g' => 6,  'fat_g' => 5,  'carbs_g' => 8, 'fiber_g' => 0, 'sugar_g' => 8, 'saturated_fat_g' => 3];
        if (preg_match('/sugar|honey|syrup/', $name))
            return ['kcal_per_100g' => 310, 'protein_g' => 0,  'fat_g' => 0,  'carbs_g' => 80, 'fiber_g' => 0, 'sugar_g' => 80, 'saturated_fat_g' => 0];
        return     ['kcal_per_100g' => 35,  'protein_g' => 2,  'fat_g' => 0,  'carbs_g' => 6, 'fiber_g' => 1, 'sugar_g' => 2, 'saturated_fat_g' => 0];
    }
}