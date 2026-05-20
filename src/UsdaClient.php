<?php

require_once __DIR__ . '/Database.php';

class UsdaClient {

    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    /**
     * Restituisce kcal/macro per 100g di un ingrediente.
     * Cerca prima in cache DB, poi chiama USDA, poi stima per categoria.
     */
    public function getMacros(string $name): array {
        // 1. Cache
        $stmt = $this->db->prepare('SELECT kcal_per_100g, protein_g, fat_g, carbs_g FROM ingredients WHERE name = ?');
        $stmt->execute([$name]);
        $cached = $stmt->fetch();
        if ($cached) return $cached;

        // 2. USDA
        $url    = USDA_URL . '?' . http_build_query(['query' => $name, 'api_key' => USDA_API_KEY, 'pageSize' => 1, 'dataType' => 'Foundation Food,SR Legacy']);
        $json   = @file_get_contents($url);
        $data   = $json ? json_decode($json, true) : null;
        $macros = null;
        $source = 'estimated';

        if (!empty($data['foods'][0]['foodNutrients'])) {
            $macros = $this->extractNutrients($data['foods'][0]['foodNutrients']);
            $source = 'usda';
        }

        // 3. Stima per categoria
        if (!$macros) {
            $macros = $this->estimate($name);
        }

        // Salva in cache
        $ins = $this->db->prepare(
            'INSERT OR IGNORE INTO ingredients (name, kcal_per_100g, protein_g, fat_g, carbs_g, source)
             VALUES (?, ?, ?, ?, ?, ?)
        );
        $ins->execute([$name, $macros['kcal_per_100g'], $macros['protein_g'], $macros['fat_g'], $macros['carbs_g'], $source]);

        return $macros;
    }

    private function extractNutrients(array $nutrients): array {
        $out = ['kcal_per_100g' => 0, 'protein_g' => 0, 'fat_g' => 0, 'carbs_g' => 0];
        foreach ($nutrients as $n) {
            match ((int)($n['nutrientId'] ?? 0)) {
                1008 => $out['kcal_per_100g'] = (float) $n['value'],
                1003 => $out['protein_g']     = (float) $n['value'],
                1004 => $out['fat_g']         = (float) $n['value'],
                1005 => $out['carbs_g']       = (float) $n['value'],
                default => null,
            };
        }
        return $out;
    }

    private function estimate(string $name): array {
        if (preg_match('/chicken|beef|pork|fish|salmon|tuna|meat|turkey/', $name))
            return ['kcal_per_100g' => 190, 'protein_g' => 22, 'fat_g' => 9,  'carbs_g' => 0];
        if (preg_match('/rice|pasta|bread|flour|oat|wheat/', $name))
            return ['kcal_per_100g' => 355, 'protein_g' => 7,  'fat_g' => 1,  'carbs_g' => 74];
        if (preg_match('/oil|butter|cream|lard/', $name))
            return ['kcal_per_100g' => 720, 'protein_g' => 0,  'fat_g' => 80, 'carbs_g' => 0];
        if (preg_match('/milk|yogurt|cheese/', $name))
            return ['kcal_per_100g' => 100, 'protein_g' => 6,  'fat_g' => 5,  'carbs_g' => 8];
        if (preg_match('/sugar|honey|syrup/', $name))
            return ['kcal_per_100g' => 310, 'protein_g' => 0,  'fat_g' => 0,  'carbs_g' => 80];
        return     ['kcal_per_100g' => 35,  'protein_g' => 2,  'fat_g' => 0,  'carbs_g' => 6];
    }
}