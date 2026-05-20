<?php

class Validator {

    public function validate(array $input): array {
        $calories  = (int)   ($input['calories']  ?? 2000);
        $diet      = (string)($input['diet']      ?? 'none');
        $allergies = (string)($input['allergies'] ?? '');
        $budget    = (float) ($input['budget']    ?? 0);
        $days      = (int)   ($input['days']      ?? 7);

        if ($calories < 1000 || $calories > 5000)
            throw new InvalidArgumentException('calories deve essere tra 1000 e 5000');

        $validDiets = ['none', 'vegan', 'vegetarian', 'lactose_free', 'gluten_free', 'pescatarian'];
        if (!in_array($diet, $validDiets, true))
            throw new InvalidArgumentException('diet non valido');

        if ($days < 1 || $days > 7)
            throw new InvalidArgumentException('days deve essere tra 1 e 7');

        if ($budget < 0)
            throw new InvalidArgumentException('budget non può essere negativo');

        $allergyList = [];
        if ($allergies !== '') {
            $allergyList = array_values(array_filter(array_map('trim', explode(',', $allergies))));
        }

        return [
            'calories'  => $calories,
            'diet'      => $diet,
            'allergies' => $allergyList,
            'budget'    => $budget,
            'days'      => $days,
        ];
    }
}
