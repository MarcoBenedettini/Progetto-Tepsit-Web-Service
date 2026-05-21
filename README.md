# Mondo Gustoso Piano Pasti

Progetto scolastico Web Service REST per la creazione di piano pasti settimanale, che utilizza un database contenente ricette e valori nutrizionali dell'API 

## Requisiti

- XAMPP (Apache, MySQL e PHP 8+)
- Chiave API USDA FoodData Central (gratuita): https://fdc.nal.usda.gov/api-guide.html

## Installazione

### 1. Clona il repository
```bash
git clone https://github.com/MarcoBenedettini/Progetto-Tepsit-Web-Service.git
```

### 2. Preparazione per la configurazione del database
Avviare Apache e MySQL da XAMPP

Da terminale accedere al seguente percorso:
```bash
C:\xampp\mysql\bin
```

Entra in MySQL:
```bash
mysql -u root -p
```

Crea il database vuoto:
```bash
CREATE DATABASE mondogustoso;
```

Successivamente eseguire per uscire:
```bash
EXIT;
```
#### Importazione del dump `MondoGustoso.sql`
Scaricare il dump [MondoGustoso.sql](https://www.dropbox.com/scl/fi/muqeg75bletyhogryju2k/databaseRicette.zip?rlkey=3aly8bfxf4ikm38961cr3sp26&st=sfgu5zu8&dl=0) e decomprimilo


Da terminale:
```bash
mysql -u root -p mondogustoso < C:\Users\%Appdata%\MondoGustoso.sql
```
Aspettare qualche minuto per il caricamento.

In seguito creare le tabelle mancanti:
copia il contenuto di `create_tables.txt` e incollalo su phpmyadmin in mondogustoso nella sezione SQL

### 3. Configura il progetto
Rinonima `config.example.php` in `config.php` e aprilo modificandolo con la propria chiave USDA e le credenziali MySQL (vedi `config.example.php`)

### 4. Popola i valori nutrizionali
Da terminale eseguire:
```bash
C:\xampp\php\php.exe C:\xampp\htdocs\meal-planner\mondo-gustoso\setup\fetch_nutrition.php
```
Questo script legge gli ingredienti dal DB, chiama USDA una volta per ognuno e salva i risultati localmente. Richiede tempo proporzionale al numero di ricette.

Se bisogna aggiornare:
```bash
C:\xampp\php\php.exe C:\xampp\htdocs\meal-planner\mondo-gustoso\setup\fetch_nutrition.php --force-update
```

### 5. Abilita mod_rewrite su Apache
Assicurati che `mod_rewrite` sia attivo in XAMPP e che `AllowOverride All` sia impostato nel virtualhost.


## Utilizzo dell'API
L'endpoint principale è `GET /meal-planner/mondo-gustoso/api/plan.php`

### Endpoint
| Parametro | Tipo | Default | Descrizione |
| --- | --- | --- | --- |
| **[calories](ca://s?q=Spiega_parametro_calories)** | int | 2000 | Range consigliato: **1000–5000** |
| **[diet](ca://s?q=Spiega_parametro_diet)** | enum | none | Tipo di dieta: ``none``, ``vegan``, ``vegetarian``, ``lactose_free``, ``pescatarian`` |
| **[allergies](ca://s?q=Spiega_parametro_allergies)** | csv | — | ``peanut,gluten`` |
| **[budget](ca://s?q=Spiega_parametro_budget)** | float | 0 | **€/giorno** ``0`` = nessun limite (opzionale, messo come divertimento). |
| **[days](ca://s?q=Spiega_parametro_days)** | int | 7 | Range **1–7**. |
| **[snacks](ca://s?q=Spiega_parametro_snacks)** | int | 0 | Se 1, include uno snack aggiuntivo (riduce le quote degli altri pasti). |

### Esempio

```bash
curl "http://localhost/cartella_progetto/public/index.php?calories=1700&days=4&allergies=glutine%2Clattosio"
```

### Risposta JSON

```json
{
    "success": true,
    "inputs": {
        "calories": "1700",
        "allergies": [
            "glutine",
            "lattosio"
        ],
        "days": "4",
        "snacks": false
    },
    "summary": {
        "days": "4",
        "unique_recipes_used": 12,
        "avg_calories_per_day": 1700
    },
    "plan": [
        {
            "day": 1,
            "meals": [
                {
                    "meal": "breakfast",
                    "recipe": "Pancakes",
                    "instructions": "[\"Mix dry ingredients.\", \"Add egg, margarine and buttermilk. Stir by hand until well blended.\", \"Pour onto non-stick pan or griddle with a little oil added.\", \"Cook until bubbly and turn over.\"]",
                    "recipe_id": 101,
                    "portion_factor": 1.29,
                    "calories": 528.1,
                    "protein_g": 8.6,
                    "fat_g": 31.2,
                    "carbs_g": 53.7,
                    "fiber_g": 1,
                    "sugar_g": 27.3,
                    "saturated_fat_g": 12.9
                },
                {
                    "meal": "lunch",
                    "recipe": "Vegetable Soup",
                    "instructions": "[\"Slice the vegetables.\", \"Cook in small amount of water until tender.\", \"Add the cooked macaroni, green beans and peas.\", \"Add bay leaf and Italian seasoning.\", \"Cook beef or turkey until it changes color.\", \"Add to vegetables.\", \"Pour in tomato juice.\", \"Bring to boil. Reduce to simmer.\", \"If soup is too thick, add more tomato juice.\"]",
                    "recipe_id": 145,
                    "portion_factor": 3.1,
                    "calories": 434.2,
                    "protein_g": 16.5,
                    "fat_g": 9.9,
                    "carbs_g": 78.1,
                    "fiber_g": 18.6,
                    "sugar_g": 18.6,
                    "saturated_fat_g": 2.9
                },
                {
                    "meal": "dinner",
                    "recipe": "Summer Chicken",
                    "instructions": "[\"Double recipe for more chicken.\"]",
                    "recipe_id": 32,
                    "portion_factor": 2.59,
                    "calories": 737.7,
                    "protein_g": 19.5,
                    "fat_g": 55.4,
                    "carbs_g": 53,
                    "fiber_g": 23.1,
                    "sugar_g": 2.6,
                    "saturated_fat_g": 5.2
                }
            ],
            "totals": {
                "calories": 1700,
                "protein_g": 44.6,
                "fat_g": 96.5,
                "carbs_g": 184.8,
                "fiber_g": 42.7,
                "sugar_g": 48.5,
                "saturated_fat_g": 21
            }
        },
        {
            "day": 2,
            "meals": [
                {
                    "meal": "breakfast",
                    "recipe": "Egg Drop Soup",
                    "instructions": "[\"In a large saucepan or Dutch oven, heat oil over medium heat. Add onion, garlic, ginger and pork; cook and stir until tender. Stir in broth, soy sauce, pepper and sesame oil; bring to a boil. Add carrots; simmer 15 minutes.\", \"Add all remaining ingredients except eggs; bring to a boil.\", \"Reduce heat to low.\", \"Stir in eggs with a fork to separate into strands.\"]",
                    "recipe_id": 263,
                    "portion_factor": 1.82,
                    "calories": 462,
                    "protein_g": 12.5,
                    "fat_g": 30,
                    "carbs_g": 38.3,
                    "fiber_g": 5.2,
                    "sugar_g": 7.3,
                    "saturated_fat_g": 4.9
                },
                {
                    "meal": "lunch",
                    "recipe": "Pear-Lime Salad",
                    "instructions": "[\"Drain pears, reserving juice.\", \"Bring juice to a boil, stirring constantly.\", \"Remove from heat.\", \"Add gelatin, stirring until dissolved.\", \"Let cool slightly.\", \"Coarsely chop pear halves. Combine cream cheese and yogurt; beat at medium speed of electric mixer until smooth.\", \"Add gelatin and beat well.\", \"Stir in pears.\", \"Pour into an oiled 4-cup mold or Pyrex dish.\", \"Chill.\"]",
                    "recipe_id": 39,
                    "portion_factor": 2.72,
                    "calories": 588.5,
                    "protein_g": 15.5,
                    "fat_g": 23.7,
                    "carbs_g": 83,
                    "fiber_g": 2.5,
                    "sugar_g": 67.7,
                    "saturated_fat_g": 13.9
                },
                {
                    "meal": "dinner",
                    "recipe": "Cherry Pizza",
                    "instructions": "[\"Grease a 9 x 12-inch cake pan.\", \"Spread cherry pie mix.\", \"Sift cake mix and spread it dry over the cherries.\", \"Top with ground nuts.\", \"Melt butter and pour over top.\", \"Bake 45 minutes in a 350\\u00b0 oven.\"]",
                    "recipe_id": 33,
                    "portion_factor": 1.3,
                    "calories": 649.5,
                    "protein_g": 7.3,
                    "fat_g": 50,
                    "carbs_g": 45.4,
                    "fiber_g": 1.5,
                    "sugar_g": 17.8,
                    "saturated_fat_g": 22.8
                }
            ],
            "totals": {
                "calories": 1700,
                "protein_g": 35.3,
                "fat_g": 103.7,
                "carbs_g": 166.7,
                "fiber_g": 9.2,
                "sugar_g": 92.8,
                "saturated_fat_g": 41.6
            }
        },
        {
            "day": 3,
            "meals": [
                {
                    "meal": "breakfast",
                    "recipe": "Bacon And Egg Breakfast Bake",
                    "instructions": "[\"Preheat oven to 450\\u00b0.\", \"Spread bacon in bottom of 9-inch pie plate.\", \"Sprinkle with cheese.\", \"Beat eggs with milk and pepper. Pour over cheese.\", \"Bake for 15 minutes.\", \"Reduce heat to 350\\u00b0. Bake for 10 to 15 minutes longer, until browned and firm in center.\", \"Place pie plate on wire rack to cool for 10 minutes.\", \"Cut in wedges.\", \"Yields 10 servings, 249 calories per serving.\"]",
                    "recipe_id": 255,
                    "portion_factor": 1.94,
                    "calories": 477.5,
                    "protein_g": 24.7,
                    "fat_g": 19.1,
                    "carbs_g": 51.4,
                    "fiber_g": 3.5,
                    "sugar_g": 5.6,
                    "saturated_fat_g": 9.1
                },
                {
                    "meal": "lunch",
                    "recipe": "Taco Dip",
                    "instructions": "[\"Mix together cream cheese and mayonnaise.\", \"Spread on to a large platter or two dinner plates.\", \"Freeze for 15 minutes. Spread taco sauce evenly on top.\", \"Layer remaining ingredients in the written order.\", \"Serve with tortilla chips.\"]",
                    "recipe_id": 59,
                    "portion_factor": 2.81,
                    "calories": 666.5,
                    "protein_g": 21.3,
                    "fat_g": 37.6,
                    "carbs_g": 66,
                    "fiber_g": 7.9,
                    "sugar_g": 25.3,
                    "saturated_fat_g": 13.8
                },
                {
                    "meal": "dinner",
                    "recipe": "Jewell Ball'S Chicken",
                    "instructions": "[\"Place chipped beef on bottom of baking dish.\", \"Place chicken on top of beef.\", \"Mix soup and cream together; pour over chicken. Bake, uncovered, at 275\\u00b0 for 3 hours.\"]",
                    "recipe_id": 2,
                    "portion_factor": 2.81,
                    "calories": 555.9,
                    "protein_g": 32,
                    "fat_g": 39.1,
                    "carbs_g": 18.5,
                    "fiber_g": 1.1,
                    "sugar_g": 2.8,
                    "saturated_fat_g": 14.4
                }
            ],
            "totals": {
                "calories": 1699.9,
                "protein_g": 78,
                "fat_g": 95.8,
                "carbs_g": 135.9,
                "fiber_g": 12.5,
                "sugar_g": 33.7,
                "saturated_fat_g": 37.3
            }
        },
        {
            "day": 4,
            "meals": [
                {
                    "meal": "breakfast",
                    "recipe": "Egg Cheese Souffle",
                    "instructions": "[\"In greased baking dish, place croutons and Velveeta.\", \"Mix eggs, milk, mustard, salt, onion powder and pepper.\", \"Pour on top of cheese.\", \"Place crumbled bacon on top.\", \"Bake at 325\\u00b0 for 50 minutes.\"]",
                    "recipe_id": 187,
                    "portion_factor": 1.08,
                    "calories": 477.3,
                    "protein_g": 6.7,
                    "fat_g": 31.5,
                    "carbs_g": 43.5,
                    "fiber_g": 4.5,
                    "sugar_g": 2.9,
                    "saturated_fat_g": 10.4
                },
                {
                    "meal": "lunch",
                    "recipe": "One Bowl Chocolate Fudge(Microwave)",
                    "instructions": "[\"Microwave chocolate and milk in 1 1\/2-quart microwavable bowl on High 1 minute; stir well.\", \"Microwave 1 minute longer. Stir until chocolate is completely melted and smooth.\", \"Stir in vanilla, salt and walnuts.\", \"Spread into greased 9 x 5-inch loaf pan. Refrigerate 30 minutes or until firm.\", \"Cut into squares.\"]",
                    "recipe_id": 231,
                    "portion_factor": 1.61,
                    "calories": 762,
                    "protein_g": 10,
                    "fat_g": 52.8,
                    "carbs_g": 52,
                    "fiber_g": 2.9,
                    "sugar_g": 41.2,
                    "saturated_fat_g": 26.7
                },
                {
                    "meal": "dinner",
                    "recipe": "Chicken Pot Pie",
                    "instructions": "[\"Combine the first five ingredients and place in a 9 x 12-inch casserole dish.\", \"Mix next four ingredients and pour over top of chicken mixture.\", \"Do not stir.\", \"Bake at 425\\u00b0 for 45 minutes to one hour until crust rises and browns.\"]",
                    "recipe_id": 157,
                    "portion_factor": 2.8,
                    "calories": 460.7,
                    "protein_g": 19.1,
                    "fat_g": 14.4,
                    "carbs_g": 77.2,
                    "fiber_g": 5.6,
                    "sugar_g": 7.3,
                    "saturated_fat_g": 3.1
                }
            ],
            "totals": {
                "calories": 1700,
                "protein_g": 35.8,
                "fat_g": 98.7,
                "carbs_g": 172.7,
                "fiber_g": 13,
                "sugar_g": 51.4,
                "saturated_fat_g": 40.2
            }
        }
    ]
}
```
