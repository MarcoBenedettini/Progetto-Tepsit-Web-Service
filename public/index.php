<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MealPlanner — Piano Pasti</title>
<style>
body {
    font-family: Arial, sans-serif;
    font-size: 15px;
    max-width: 860px;
    margin: 30px auto;
    padding: 0 16px;
    color: #222;
    background: #fff;
}
h1 { font-size: 1.5rem; margin-bottom: 4px; }
h2 { font-size: 1.1rem; margin: 24px 0 8px; }
p.sub { color: #555; margin-bottom: 20px; font-size: .9rem; }
hr { border: none; border-top: 1px solid #ddd; margin: 24px 0; }

form { background: #f7f7f7; border: 1px solid #ddd; padding: 18px; border-radius: 4px; }
.row { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 14px; }
.field { display: flex; flex-direction: column; gap: 4px; min-width: 160px; }
label { font-size: .85rem; font-weight: bold; color: #444; }
input[type="number"], select {
    padding: 6px 8px;
    border: 1px solid #bbb;
    border-radius: 3px;
    font-size: .95rem;
    width: 100%;
}
.hint { font-size: .78rem; color: #888; }
.checks { display: flex; flex-wrap: wrap; gap: 8px 18px; margin-top: 6px; }
.checks label { font-weight: normal; display: flex; align-items: center; gap: 5px; font-size: .9rem; }
button[type="submit"] {
    margin-top: 14px;
    padding: 8px 24px;
    background: #2c6b2f;
    color: #fff;
    border: none;
    border-radius: 3px;
    font-size: .95rem;
    cursor: pointer;
}
button[type="submit"]:hover { background: #245227; }

table { border-collapse: collapse; width: 100%; }
table th, table td { border: 1px solid #ddd; padding: 7px 10px; text-align: left; font-size: .9rem; }
table th { background: #f0f0f0; font-weight: bold; }
table tr:nth-child(even) td { background: #fafafa; }

.badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: .8rem; font-weight: bold; color: #fff; }
.badge-col { background: #c8860a; }
.badge-pra { background: #2c6b2f; }
.badge-cen { background: #2a4a7f; }

.day-section { border: 1px solid #ddd; border-radius: 4px; margin-bottom: 14px; }
.day-header {
    background: #eee;
    padding: 7px 12px;
    font-size: .95rem;
    border-radius: 4px 4px 0 0;
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 4px;
}
.day-header span { font-weight: normal; font-size: .85rem; color: #555; }
.day-body { padding: 10px 12px; }

details { margin-top: 20px; }
details summary { cursor: pointer; font-size: .85rem; color: #555; }
pre { background: #f4f4f4; border: 1px solid #ddd; padding: 12px; font-size: .78rem; overflow-x: auto; max-height: 320px; border-radius: 3px; }
.alert { padding: 10px 14px; border: 1px solid #e5a000; background: #fff8e6; border-radius: 3px; font-size: .9rem; }
</style>
</head>
<body>
<?php
$submitted = isset($_GET['calories']);

$cal       = (int)($_GET['calories'] ?? 2000);
$diet      = (string)($_GET['diet'] ?? 'none');
$budget    = (float)($_GET['budget'] ?? 0);
$days      = (int)($_GET['days'] ?? 7);
$allergies = (string)($_GET['allergies'] ?? '');

$dietLabels = [
    'none'         => 'Nessuna',
    'vegan'        => 'Vegana',
    'vegetarian'   => 'Vegetariana',
    'lactose_free' => 'Senza lattosio',
    'pescatarian'  => 'Pescatariana',
];
$allergyOptions  = ['glutine','lattosio','frutta secca','uova','pesce','crostacei','soia','sesamo'];
$activeAllergies = array_filter(array_map('trim', explode(',', $allergies)));

//Chiamata all'API
$data = null;
if ($submitted) {
    $apiUrl = 'http://localhost/mondogustoso/api/plan.php?' . http_build_query($_GET);
    $raw    = @file_get_contents($apiUrl);
    $data   = $raw ? json_decode($raw, true) : null;
}

$dayNames = ['Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato','Domenica'];
?>

<h1>Piano Pasti Settimanale</h1>
<p class="sub">Inserisci le tue preferenze e genera un piano pasti personalizzato.</p>

<form method="GET" action="">
    <div class="row">
        <div class="field">
            <label for="calories">Calorie giornaliere *</label>
            <input type="number" id="calories" name="calories" min="1000" max="5000" step="50" value="<?= $cal ?>" required>
            <span class="hint">Range: 1000 – 5000 kcal</span>
        </div>
        <div class="field">
            <label for="diet">Tipo di dieta</label>
            <select id="diet" name="diet">
                <?php foreach ($dietLabels as $val => $lbl): ?>
                <option value="<?= $val ?>" <?= $diet === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="budget">Budget (€/giorno)</label>
            <input type="number" id="budget" name="budget" min="0" max="100" step="0.5" value="<?= $budget ?>">
            <span class="hint">0 = nessun limite</span>
        </div>
        <div class="field">
            <label for="days">Numero di giorni *</label>
            <input type="number" id="days" name="days" min="1" max="7" step="1" value="<?= $days ?>" required>
            <span class="hint">Da 1 a 7</span>
        </div>
    </div>
    <div>
        <label>Allergie / Intolleranze</label>
        <div class="checks">
            <?php foreach ($allergyOptions as $a): ?>
            <label>
                <input type="checkbox" class="allergy-cb" value="<?= $a ?>" <?= in_array($a, $activeAllergies) ? 'checked' : '' ?>>
                <?= ucfirst($a) ?>
            </label>
            <?php endforeach; ?>
        </div>
        <input type="hidden" id="allergies" name="allergies" value="<?= htmlspecialchars($allergies) ?>">
    </div>
    <button type="submit">Genera Piano</button>
</form>

<?php if ($submitted): ?>
<hr>

<?php if (!$data || !$data['success']): ?>
<div class="alert">Nessun piano generato. Verifica che il backend sia in esecuzione e riprova.</div>

<?php else: ?>
<h2>
    Piano generato — <?= $days ?> <?= $days === 1 ? 'giorno' : 'giorni' ?>
    (<?= htmlspecialchars($dietLabels[$diet] ?? $diet) ?>)
    <?php if ($allergies): ?> · intolleranze: <?= htmlspecialchars($allergies) ?><?php endif; ?>
</h2>

<table style="margin-bottom:18px;">
    <thead>
        <tr><th>Giorni</th><th>Ricette uniche</th><th>Ingredienti distinti</th><th>kcal medie/giorno</th><th>Costo totale</th></tr>
    </thead>
    <tbody>
        <tr>
            <td><?= $data['summary']['days'] ?></td>
            <td><?= $data['summary']['unique_recipes_used'] ?></td>
            <td><?= $data['summary']['unique_ingredients'] ?></td>
            <td><?= $data['summary']['avg_calories_per_day'] ?> kcal</td>
            <td><?= number_format($data['summary']['total_cost_eur'], 2) ?> €</td>
        </tr>
    </tbody>
</table>

<?php foreach ($data['plan'] as $day):
    $name = $dayNames[$day['day'] - 1] ?? 'Giorno '.$day['day'];
?>
<div class="day-section">
    <div class="day-header">
        <strong>Giorno <?= $day['day'] ?> — <?= $name ?></strong>
        <span><?= $day['totals']['calories'] ?> kcal &nbsp;·&nbsp; <?= $day['totals']['protein_g'] ?> g proteine &nbsp;·&nbsp; <?= number_format($day['totals']['cost_eur'], 2) ?> €</span>
    </div>
    <div class="day-body">
        <table>
            <thead>
                <tr><th>Pasto</th><th>Ricetta</th><th>Calorie</th><th>Proteine</th><th>Costo</th></tr>
            </thead>
            <tbody>
            <?php foreach ($day['meals'] as $m):
                $badge = match($m['meal']) { 'Colazione' => 'badge-col', 'Pranzo' => 'badge-pra', default => 'badge-cen' };
            ?>
            <tr>
                <td><span class="badge <?= $badge ?>"><?= ucfirst($m['meal']) ?></span></td>
                <td><?= htmlspecialchars($m['recipe']) ?></td>
                <td><?= $m['calories'] ?> kcal</td>
                <td><?= $m['protein_g'] ?> g</td>
                <td><?= number_format($m['cost_eur'], 2) ?> €</td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>

<details>
    <summary>Mostra risposta JSON dell'API</summary>
    <pre><?= htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
</details>

<?php endif; ?>
<?php endif; ?>

<script>
document.querySelectorAll('.allergy-cb').forEach(cb =>
    cb.addEventListener('change', () => {
        document.getElementById('allergies').value =
            [...document.querySelectorAll('.allergy-cb:checked')].map(c => c.value).join(',');
    })
);
</script>
</body>
</html>