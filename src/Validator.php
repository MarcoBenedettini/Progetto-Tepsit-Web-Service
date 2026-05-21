<?php
class Validator {
    public function validate($input) {

        // Calorie: intero tra 1000 e 5000, default 2000
        if (isset($input['calories'])) {
            $calories = $input['calories'];
        } else {
            $calories = 2000;
        }
        if ($calories < 1000 || $calories > 5000) {
            throw new Exception('calories deve essere tra 1000 e 5000');
        }

        // Giorni: intero tra 1 e 7, default 7
        if (isset($input['days'])) {
            $days = $input['days'];
        } else {
            $days = 7;
        }
        if ($days < 1 || $days > 7) {
            throw new Exception('days deve essere tra 1 e 7');
        }

        // Allergie: stringa separata da virgole, trasformata in array pulito
        if (isset($input['allergies'])) {
            $allergiesStr = $input['allergies'];
        } else {
            $allergiesStr = '';
        }
        $allergyList = array();
        if ($allergiesStr !== '') {
            $parts = array_map('trim', explode(',', $allergiesStr));
            foreach ($parts as $part) {
                if ($part !== '') {
                    $allergyList[] = $part;
                }
            }
        }

        return array(
            'calories' => $calories,
            'days'     => $days,
            'allergies' => $allergyList,
        );
    }
}