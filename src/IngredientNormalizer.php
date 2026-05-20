<?php

class IngredientNormalizer {

    private const STRIP = [
        'fresh', 'dried', 'frozen', 'cooked', 'raw',
        'chopped', 'diced', 'sliced', 'minced', 'ground', 'grated',
        'large', 'small', 'medium', 'whole', 'boneless', 'skinless',
        'unsalted', 'salted', 'organic', 'homemade', 'canned',
    ];

    private const KEEP_PLURAL = ['asparagus', 'hummus', 'couscous', 'oats', 'greens', 'lentils'];

    /**
     * Riceve il JSON della colonna NER e restituisce un array di nomi puliti.
     * Esempio: '["onions","olive oil","fresh garlic"]' → ['onion','olive oil','garlic']
     */
    public function fromNER(string $raw): array {
        $items = json_decode($raw, true);
        if (!is_array($items)) return [];

        $result = [];
        foreach ($items as $item) {
            $name = $this->normalize((string) $item);
            if ($name !== '') $result[] = $name;
        }

        return array_values(array_unique($result));
    }

    public function normalize(string $raw): string {
        $s = strtolower(trim($raw));
        $s = preg_replace('/[^a-z\s]/', '', $s);

        foreach (self::STRIP as $word) {
            $s = preg_replace('/\b' . $word . '\b/', '', $s);
        }

        $s = preg_replace_callback('/\b([a-z]{3,})s\b/', function ($m) {
            return in_array($m[0], self::KEEP_PLURAL) ? $m[0] : $m[1];
        }, $s);

        return trim(preg_replace('/\s+/', ' ', $s));
    }
}