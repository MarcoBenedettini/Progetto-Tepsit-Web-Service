# Mondo Gustoso Piano Pasti

Progetto Web Service 

## Requisiti

- XAMPP (Apache, MySQL e PHP 8)
- Chiave API USDA FoodData Central (gratuita): https://fdc.nal.usda.gov/api-guide.html

## Installazione

### 1. Clona il repository
```bash
git clone https://github.com/MarcoBenedettini/Progetto-Tepsit-Web-Service.git
```

### 2. Configura il database
Importare il dump `MondoGustoso.sql` in phpMyAdmin:
```bash
mysql -u root mondogustoso < MondoGustoso.sql
```
Il dump non è nel repository per via delle dimensioni (2GB). 

Successivamente creare le tabelle mancanti:
```bash
mysql -u root mondogustoso < setup/create_tables.sql
```

### 3. Configura il progetto
Copia il file di configurazione e inserisci i tuoi dati:
```bash
cp config.example.php config.php
```
Modifica `config.php` con la tua chiave USDA e le credenziali MySQL (vedi `config.example.php`)

### 4. Popola i valori nutrizionali
```bash
php setup/fetch_nutrition.php
```
Questo script legge gli ingredienti dal DB, chiama USDA una volta per ognuno e salva i risultati localmente. Richiede tempo proporzionale al numero di ricette.

### 5. Abilita mod_rewrite su Apache
Assicurati che `mod_rewrite` sia attivo in XAMPP e che `AllowOverride All` sia impostato nel virtualhost.


## Utilizzo

### Endpoint
| Parametro | Tipo | Default | Descrizione |
| --- | --- | --- | --- |
| **[calories](ca://s?q=Spiega_parametro_calories)** | int | 2000 | Range consigliato: **1000–5000** |
| **[diet](ca://s?q=Spiega_parametro_diet)** | enum | none | Tipo di dieta: ``none``, ``vegan``, ``vegetarian``, ``lactose_free``, ``pescatarian`` |
| **[allergies](ca://s?q=Spiega_parametro_allergies)** | csv | — | ``peanut,gluten`` |
| **[budget](ca://s?q=Spiega_parametro_budget)** | float | 0 | **€/giorno** ``0`` = nessun limite. |
| **[days](ca://s?q=Spiega_parametro_days)** | int | 7 | Range **1–7**. |


### Esempio

```bash
curl "http://localhost/meal-planner/mondo-gustoso/api/plan.php?calories=1800&days=7"
```

### Risposta

```json
{
    "success": true,
    "inputs": {
        "calories": 1800,
        "diet": "none",
        "allergies": [],
        "budget": 0,
        "days": 7
    },
    "summary": {
        "days": 7,
        "unique_recipes_used": 21,
        "avg_calories_per_day": 1875.1
    },
    "plan": [
        {
            "day": 1,
            "meals": [
                {
                    "meal": "breakfast",
                    "recipe": "Nutty Muffins",
                    "recipe_id": 3495,
                    "portion_factor": 1.39,
                    "calories": 450.4,
                    "protein_g": 10.6
                },
                {
                    "meal": "lunch",
                    "recipe": "One Bowl Chocolate Fudge",
                    "recipe_id": 4797,
                    "portion_factor": 2,
                    "calories": 642,
                    "protein_g": 15.8
                },
                {
                    "meal": "dinner",
                    "recipe": "Chili",
                    "recipe_id": 5142,
                    "portion_factor": 1.87,
                    "calories": 630.2,
                    "protein_g": 42.1
                }
            ],
            "totals": {
                "calories": 1722.6,
                "protein_g": 68.5
            }
        },
        {
            "day": 2,
            "meals": [
                {
                    "meal": "breakfast",
                    "recipe": "Breakfast Bars",
                    "recipe_id": 1135,
                    "portion_factor": 1.16,
                    "calories": 451.2,
                    "protein_g": 43.5
                },
                {
                    "meal": "lunch",
                    "recipe": "Souper Tuna Crunch",
                    "recipe_id": 112,
                    "portion_factor": 0.65,
                    "calories": 718.3,
                    "protein_g": 19.8
                },
                {
                    "meal": "dinner",
                    "recipe": "Chili Con Cornbread",
                    "recipe_id": 3661,
                    "portion_factor": 1.5,
                    "calories": 631.5,
                    "protein_g": 19.5
                }
            ],
            "totals": {
                "calories": 1801,
                "protein_g": 82.8
            }
        },
        {
            "day": 3,
            "meals": [
                {
                    "meal": "breakfast",
                    "recipe": "Egg Custard Pie(9-Inch Pie)",
                    "recipe_id": 6877,
                    "portion_factor": 0.83,
                    "calories": 450.7,
                    "protein_g": 17.8
                },
                {
                    "meal": "lunch",
                    "recipe": "Tuna Pasta Salad",
                    "recipe_id": 1744,
                    "portion_factor": 1.02,
                    "calories": 723.2,
                    "protein_g": 38.3
                },
                {
                    "meal": "dinner",
                    "recipe": "Spaghetti Pie",
                    "recipe_id": 989,
                    "portion_factor": 1.25,
                    "calories": 632.5,
                    "protein_g": 46.1
                }
            ],
            "totals": {
                "calories": 1806.4,
                "protein_g": 102.2
            }
        },
        {
            "day": 4,
            "meals": [
                {
                    "meal": "breakfast",
                    "recipe": "Eggnog Mix",
                    "recipe_id": 3145,
                    "portion_factor": 1.1,
                    "calories": 451,
                    "protein_g": 7.6
                },
                {
                    "meal": "lunch",
                    "recipe": "Potato Soup",
                    "recipe_id": 3526,
                    "portion_factor": 0.82,
                    "calories": 715.9,
                    "protein_g": 46.2
                },
                {
                    "meal": "dinner",
                    "recipe": "Asparagus Casserole",
                    "recipe_id": 2728,
                    "portion_factor": 1.59,
                    "calories": 628.1,
                    "protein_g": 21.3
                }
            ],
            "totals": {
                "calories": 1795,
                "protein_g": 75.1
            }
        },
        {
            "day": 5,
            "meals": [
                {
                    "meal": "breakfast",
                    "recipe": "Egg Drop Soup",
                    "recipe_id": 263,
                    "portion_factor": 0.5,
                    "calories": 1048.5,
                    "protein_g": 12.6
                },
                {
                    "meal": "lunch",
                    "recipe": "Polish Split Pea Soup(Do Not Stir This Soup)",
                    "recipe_id": 7474,
                    "portion_factor": 1.42,
                    "calories": 721.4,
                    "protein_g": 16.6
                },
                {
                    "meal": "dinner",
                    "recipe": "Chicken And Dumplings",
                    "recipe_id": 1373,
                    "portion_factor": 1.76,
                    "calories": 630.1,
                    "protein_g": 13.2
                }
            ],
            "totals": {
                "calories": 2400,
                "protein_g": 42.4
            }
        },
        {
            "day": 6,
            "meals": [
                {
                    "meal": "breakfast",
                    "recipe": "Oatmeal Cookies",
                    "recipe_id": 6143,
                    "portion_factor": 0.95,
                    "calories": 452.2,
                    "protein_g": 30.4
                },
                {
                    "meal": "lunch",
                    "recipe": "Crabmeat Quesadillas",
                    "recipe_id": 2652,
                    "portion_factor": 0.79,
                    "calories": 718.9,
                    "protein_g": 17.4
                },
                {
                    "meal": "dinner",
                    "recipe": "Chicken Mexicali Casserole",
                    "recipe_id": 2651,
                    "portion_factor": 1.64,
                    "calories": 629.8,
                    "protein_g": 38.5
                }
            ],
            "totals": {
                "calories": 1800.9,
                "protein_g": 86.3
            }
        },
        {
            "day": 7,
            "meals": [
                {
                    "meal": "breakfast",
                    "recipe": "Egg Cheese Souffle",
                    "recipe_id": 187,
                    "portion_factor": 1.2,
                    "calories": 451.2,
                    "protein_g": 14.9
                },
                {
                    "meal": "lunch",
                    "recipe": "Ruth'S Jello Salad",
                    "recipe_id": 1205,
                    "portion_factor": 1.89,
                    "calories": 720.1,
                    "protein_g": 14.7
                },
                {
                    "meal": "dinner",
                    "recipe": "Pizza Dippers",
                    "recipe_id": 908,
                    "portion_factor": 0.8,
                    "calories": 628.8,
                    "protein_g": 12.1
                }
            ],
            "totals": {
                "calories": 1800.1,
                "protein_g": 41.7
            }
        }
    ]
}
```
