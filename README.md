# MealPlanner — Piano Pasti Settimanale

## Struttura
```
progetto/
├── meal_planner.db        ← Database SQLite (già popolato con 994 ricette)
├── api/
│   ├── error.php
│   └── plan.php           ← Endpoint API
├── public/
│   └── index.php          ← Frontend HTML
└── src/
    ├── Database.php
    ├── DietPlanner.php
    ├── IngredientNormalizer.php
    ├── RecipeRepository.php
    ├── UsdaClient.php
    └── Validator.php
```

## Avvio (con PHP built-in server)
Dalla cartella del progetto:
```bash
php -S localhost:8000 -t public
```
Poi apri http://localhost:8000

L'API sarà disponibile su http://localhost:8000/../api/plan.php  
oppure avvia un secondo server:
```bash
php -S localhost:8001
```
e accedi a http://localhost:8000 (il frontend chiama http://localhost/api/plan.php — adatta l'URL in public/index.php se necessario).

## Parametri API (GET /api/plan.php)
| Parametro  | Tipo    | Default | Note                                              |
|------------|---------|---------|---------------------------------------------------|
| calories   | int     | 2000    | 1000–5000                                         |
| diet       | string  | none    | none, vegan, vegetarian, lactose_free, gluten_free, pescatarian |
| allergies  | string  | ""      | Lista separata da virgole (es. "glutine,lattosio")|
| days       | int     | 7       | 1–7                                               |
| budget     | float   | 0       | 0 = nessun limite (non ancora implementato)       |

## Note tecniche
- Database SQLite (`meal_planner.db`) — non serve MySQL/MariaDB
- Macros degli ingredienti stimate per categoria (nessuna chiave USDA necessaria)
- 994 ricette, 1087 ingredienti, 3 fasce orarie: breakfast, lunch, dinner
