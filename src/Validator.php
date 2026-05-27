<?php
class Validator {
    public function validate($input) {
        $calories = 2000;
		if (isset($input['calories'])) {
    		$calories = $input['calories'];
		}

		$allergies = '';
		if (isset($input['allergies'])) {
    		$allergies = $input['allergies'];
		}

		$days = 7;
		if (isset($input['days'])) {
    		$days = $input['days'];
		}
	
		$snacks = false;
		if (isset($input['snacks']) && $input['snacks'] == 1) {
    		$snacks = true;
		}



		if ($calories < 1000 || $calories > 5000) {
    		throw new InvalidArgumentException('calories deve essere tra 1000 e 5000');
		}
		
		if ($days < 1 || $days > 7) {
    		throw new InvalidArgumentException('days deve essere tra 1 e 7');
		}


        // Trasforma la stringa delle allergie in un array di allergie
        // ES --> "glutine,lattosio" → array('glutine', 'lattosio')
        $allergyList = array();
        if ($allergies !== '') {
            $temp = explode(',', $allergies);
            $temp = array_map('trim', $temp);	// toglie spazi intorno
            
            $temp = array_filter($temp);	// rimuove elementi vuoti
            
            $allergyList = array_values($temp);	// reindicizza l'array
        }
        return array('calories' => $calories,
            'allergies' => $allergyList,
            'days' => $days,
            'snacks' => $snacks,
        );
    }
}