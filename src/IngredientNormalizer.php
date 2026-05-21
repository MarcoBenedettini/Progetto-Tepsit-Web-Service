<?php
class IngredientNormalizer {

    // Parole da rimuovere (aggettivi, stati, preparazioni)
    private $strip = array(
        'fresh', 'dried', 'frozen', 'cooked', 'raw',
        'chopped', 'diced', 'sliced', 'minced', 'ground', 'grated',
        'large', 'small', 'medium', 'whole', 'boneless', 'skinless',
        'unsalted', 'salted', 'organic', 'homemade', 'canned',
    );

    // Parole che mantengono la 's' finale anche se sembrano plurali
    private $keepPlural = array('asparagus', 'hummus', 'couscous', 'oats', 'greens', 'lentils');

    public function fromNER($raw) {
        $items = json_decode($raw, true);
        if (!is_array($items)) {
            return array();
        }
        $result = array();
        foreach ($items as $item) {
            $name = $this->normalize($item);
            if ($name !== '') {
                $result[] = $name;
            }
        }
        return array_values(array_unique($result));
    }

    public function normalize($raw) {
        // Minuscolo e senza spazi laterali
        $s = strtolower(trim($raw));
        // Rimuove caratteri non alfabetici (tranne spazi)
        $s = preg_replace('/[^a-z\s]/', '', $s);
        // Rimuove le parole superflue
        foreach ($this->strip as $word) {
            $s = preg_replace('/\b' . $word . '\b/', '', $s);
        }
        // Gestisce plurali inglesi (es. onions -> onion) tranne eccezioni
        $keepPlural = $this->keepPlural;
        $s = preg_replace_callback('/\b([a-z]{3,})s\b/', function($m) use ($keepPlural) {
            $found = false;
            foreach ($keepPlural as $word) {
                if ($word === $m[0]) {
                    $found = true;
                }
            }
            if ($found) {
                return $m[0];
            } else {
                return $m[1];
            }
        }, $s);
        // Compatta spazi multipli
        return trim(preg_replace('/\s+/', ' ', $s));
    }
}