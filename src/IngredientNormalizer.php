<?php

// Classe per normalizzare i nomi degli ingredienti estratti dal campo NER delle ricette

class IngredientNormalizer
{
    // array di parole da rimuovere (aggettivi, stati di lavorazione, ecc.)
    private $stripWords = array(
        'fresh', 'dried', 'frozen', 'cooked', 'raw',
        'chopped', 'diced', 'sliced', 'minced', 'ground', 'grated',
        'large', 'small', 'medium', 'whole', 'boneless', 'skinless',
        'unsalted', 'salted', 'organic', 'homemade', 'canned'
    );

    // Parole che restano in forma plurale (eccezioni)
    private $keepPlural = array('asparagus', 'hummus', 'couscous', 'oats', 'greens', 'lentils');


    // Riceve il JSON della colonna NER e restituisce un array di nomi puliti
    // ES --> '["onions","olive oil","fresh garlic"]' -> ['onion','olive oil','garlic']
    public function fromNER($raw)
    {
        // Decodifica il JSON in un array PHP
        $items = json_decode($raw, true);

        // Se la decodifica fallisce o non è un array -> restituisce array vuoto
        if (!is_array($items)) {
            return array();
        }

        $result = array();

        // Normalizza ogni singolo ingrediente
        foreach ($items as $item) {
    		$name = $this->normalize($item);
    		if ($name !== '') {
        		$result[] = $name;
    		}
		}

        // Rimuove eventuali duplicati e reindirizza l'array
        $result = array_values(array_unique($result));
        return $result;
    }


    // Normalizza una singola stringa ingrediente
    // ES --> "fresh garlic" -> "garlic", "onions" -> "onion"
    public function normalize($raw)
    {
        $s = strtolower(trim($raw));	// strtolower -> converte tutte le lettere di una stringa in minuscolo

        // Rimuove tutti i caratteri che non sono lettere o spazi, mantiene solo a-z e spazi
        $s = preg_replace('/[^a-z\s]/', '', $s);	//ES --> "olive-oil!" -> "oliveoil"

        
        
        // Scorre la lista delle parole da rimuovere come fresh, chopped, dried e per ognuna la cerca nella stringa e la elimina
        foreach ($this->stripWords as $word) {
            // Cerca la parola intera delimitata da \b e la sostituisce con stringa vuota
            $s = preg_replace('/\b' . $word . '\b/', '', $s);
        }

        // Trasforma i plurali in singolari, tranne per le eccezioni in $keepPlural
        // La regex (qualsiasi parola che finisce con la lettera 's')  trova parole di almeno 3 lettere che terminano con 's'
        $s = preg_replace_callback('/\b([a-z]{3,})s\b/', array($this, 'pluralCallback'), $s);

        // Sostituisce spazi multipli con un singolo spazio e rifinisce
        $s = preg_replace('/\s+/', ' ', $s);
        $s = trim($s);
        return $s;
    }


    // Decide se mantenere il plurale o convertire in singolare.
    private function pluralCallback($matches)
    {
        $fullWord = $matches[0];   // parola con la 's' finale
        $stem = $matches[1];       // parola senza 's'

        // Se la parola è nell'elenco delle eccezioni, la lascia invariata
        if (in_array($fullWord, $this->keepPlural)) {
            return $fullWord;
        } else {
            return $stem;	// altrimenti restituisce la forma singolare
        }
    }
}