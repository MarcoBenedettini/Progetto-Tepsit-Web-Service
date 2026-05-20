<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MealPlanner</title>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    background: #f5f5f5;
    color: #111;
    line-height: 1.5;
    padding: 2rem 1rem;
}
.container {
    max-width: 1300px;
    margin: 0 auto;
}
.card {
    background: #fff;
    border: 1px solid #e0e0e0;
    padding: 1.5rem;
    margin-bottom: 2rem;
}
h1 {
    font-size: 1.75rem;
    font-weight: 400;
    letter-spacing: -0.01em;
    margin-bottom: 0.25rem;
}
.sub {
    color: #666;
    font-size: 0.875rem;
    margin-bottom: 1.5rem;
    border-left: 2px solid #ccc;
    padding-left: 0.75rem;
}
.row {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.field {
    flex: 1;
    min-width: 140px;
}
label {
    display: block;
    font-size: 0.7rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #888;
    margin-bottom: 0.25rem;
}
input, select {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid #ccc;
    background: #fff;
    font-size: 0.875rem;
    font-family: inherit;
    border-radius: 2px;
}
input:focus, select:focus {
    outline: none;
    border-color: #666;
}
.hint {
    font-size: 0.65rem;
    color: #999;
    margin-top: 0.25rem;
    display: block;
}
.check-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem 1.25rem;
    margin: 0.5rem 0 0.25rem;
}
.check-group label {
    text-transform: none;
    font-size: 0.8rem;
    color: #333;
    font-weight: normal;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    cursor: pointer;
}
.check-group input {
    width: auto;
    margin: 0;
}
button {
    background: #111;
    color: #fff;
    border: none;
    padding: 0.6rem 1.5rem;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    margin-top: 0.75rem;
    border-radius: 2px;
}
button:hover {
    background: #333;
}
button:disabled {
    background: #999;
    cursor: not-allowed;
}
.summary-bar {
    background: #fff;
    border: 1px solid #e0e0e0;
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
}
.summary-stats {
    display: flex;
    gap: 1.25rem;
    font-size: 0.8rem;
    color: #555;
}
.summary-stats strong {
    color: #000;
    font-weight: 600;
}
.day {
    background: #fff;
    border: 1px solid #e0e0e0;
    margin-bottom: 1rem;
    overflow-x: auto;
}
.day-header {
    background: #fafafa;
    padding: 0.75rem 1.25rem;
    border-bottom: 1px solid #e0e0e0;
    font-weight: 500;
    font-size: 0.9rem;
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.day-totals {
    font-size: 0.75rem;
    color: #666;
    font-weight: normal;
}
table {
    width: 100%;
    border-collapse: collapse;
}
th, td {
    padding: 0.7rem 1rem;
    text-align: left;
    border-bottom: 1px solid #eee;
    font-size: 0.85rem;
}
th {
    background: #fafafa;
    font-weight: 500;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #777;
}
tr:last-child td {
    border-bottom: none;
}
.meal-tag {
    display: inline-block;
    font-size: 0.7rem;
    font-weight: 500;
    padding: 0.15rem 0.6rem;
    background: #f0f0f0;
    color: #444;
    border-radius: 2px;
}
.meal-tag-warning {
    background: #fdeadd;
    color: #b45f06;
}
.alert {
    padding: 1rem;
    font-size: 0.875rem;
    border-left: 3px solid;
    margin-bottom: 1rem;
}
.alert-error {
    background: #fef5f5;
    border-left-color: #c00;
    color: #900;
}
details {
    margin-top: 1.5rem;
    border-top: 1px solid #eee;
    padding-top: 1rem;
}
summary {
    font-size: 0.7rem;
    color: #888;
    cursor: pointer;
}
pre {
    background: #f8f8f8;
    padding: 1rem;
    font-size: 0.7rem;
    overflow-x: auto;
    margin-top: 0.75rem;
    border: 1px solid #eee;
    font-family: monospace;
}
footer {
    margin-top: 2rem;
    font-size: 0.65rem;
    color: #aaa;
    text-align: center;
    border-top: 1px solid #eee;
    padding-top: 1.5rem;
}
.loader {
    display: inline-block;
    width: 12px;
    height: 12px;
    border: 2px solid #fff;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.4s linear infinite;
    margin-right: 0.5rem;
    vertical-align: middle;
}
@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>Piano pasti settimanale</h1>
        <p class="sub">Genera un piano personalizzato in base alle tue preferenze</p>

        <form method="GET" action="" id="plannerForm">
            <div class="row">
                <div class="field">
                    <label>Calorie / giorno</label>
                    <input type="number" name="calories" min="1000" max="5000" step="50" value="<?= htmlspecialchars($_GET['calories'] ?? 2000) ?>" required>
                    <span class="hint">1000–5000 kcal</span>
                </div>
                <div class="field">
                    <label>Dieta</label>
                    <select name="diet">
                        <?php
                        $diets = [
                            'none' => 'Nessuna restrizione',
                            'vegetarian' => 'Vegetariana',
                            'vegan' => 'Vegana',
                            'lactose_free' => 'Senza lattosio',
                            'pescatarian' => 'Pescatariana'
                        ];
                        $current = $_GET['diet'] ?? 'none';
                        foreach ($diets as $val => $label): ?>
                            <option value="<?= $val ?>" <?= $current === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Budget (€/giorno)</label>
                    <input type="number" name="budget" min="0" max="100" step="0.5" value="<?= htmlspecialchars($_GET['budget'] ?? 0) ?>">
                    <span class="hint">0 = nessun limite</span>
                </div>
                <div class="field">
                    <label>Giorni</label>
                    <input type="number" name="days" min="1" max="7" step="1" value="<?= htmlspecialchars($_GET['days'] ?? 7) ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="field" style="display: flex; align-items: center; gap: 0.5rem;">
                    <label style="text-transform: none; margin:0;">
                        <input type="checkbox" name="snacks" value="1" <?= isset($_GET['snacks']) && $_GET['snacks'] == 1 ? 'checked' : '' ?>>
                        Includi snack
                    </label>
                </div>
            </div>

            <div>
                <label>Allergie / intolleranze</label>
                <div class="check-group" id="allergyGroup">
                    <?php
                    $allergeni = ['glutine', 'lattosio', 'frutta secca', 'uova', 'pesce', 'crostacei', 'soia', 'sesamo'];
                    $attive = isset($_GET['allergies']) ? array_map('trim', explode(',', $_GET['allergies'])) : [];
                    foreach ($allergeni as $a):
                    ?>
                        <label>
                            <input type="checkbox" value="<?= $a ?>" <?= in_array($a, $attive) ? 'checked' : '' ?>>
                            <?= ucfirst($a) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="allergies" id="allergiesField" value="<?= htmlspecialchars($_GET['allergies'] ?? '') ?>">
            </div>

            <button type="submit" id="submitBtn">Genera piano</button>
        </form>
    </div>

    <?php
    $inviato = isset($_GET['calories']) && $_GET['calories'] !== '';
    $dati = null;
    $errore = false;
    $msgErrore = '';

    if ($inviato) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        $base = str_replace('/public', '', $base);
        $apiUrl = $protocol . '://' . $host . $base . '/api/plan.php?' . http_build_query($_GET);

        $ctx = stream_context_create(['http' => ['timeout' => 30, 'ignore_errors' => true]]);
        $resp = @file_get_contents($apiUrl, false, $ctx);

        if ($resp !== false) {
            $dati = json_decode($resp, true);
            if (!isset($dati['success']) || $dati['success'] !== true) {
                $errore = true;
                $msgErrore = $dati['error'] ?? 'Errore sconosciuto';
            }
        } else {
            $errore = true;
            $msgErrore = 'API non raggiungibile. Verifica che il backend sia attivo.';
        }
    }

    $nomiGiorni = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato', 'Domenica'];
    $labelPasti = ['breakfast' => 'Colazione', 'lunch' => 'Pranzo', 'dinner' => 'Cena', 'snack' => 'Snack'];
    ?>

    <?php if ($inviato && $errore): ?>
        <div class="alert alert-error">
            <strong>Errore</strong><br>
            <?= htmlspecialchars($msgErrore) ?>
        </div>
    <?php elseif ($inviato && $dati && $dati['success']): 
        $piano = $dati['plan'];
        $sommario = $dati['summary'];
        $input = $dati['inputs'];
    ?>
        <div class="summary-bar">
            <div>
                <strong>Piano per <?= $sommario['days'] ?> <?= $sommario['days'] === 1 ? 'giorno' : 'giorni' ?></strong>
                <span style="font-size:0.75rem; color:#777; margin-left:0.75rem;"><?= $diets[$input['diet']] ?? $input['diet'] ?></span>
                <?php if (!empty($input['allergies'])): ?>
                    <span style="font-size:0.7rem; color:#999; margin-left:0.5rem;">(senza <?= implode(', ', $input['allergies']) ?>)</span>
                <?php endif; ?>
                <?php if ($input['snacks']): ?>
                    <span style="font-size:0.7rem; color:#999; margin-left:0.5rem;">+ snack</span>
                <?php endif; ?>
            </div>
            <div class="summary-stats">
                <span><strong><?= $sommario['unique_recipes_used'] ?></strong> ricette</span>
                <span><strong><?= round($sommario['avg_calories_per_day']) ?></strong> kcal/media</span>
            </div>
        </div>

        <?php foreach ($piano as $giorno): 
            $idx = $giorno['day'] - 1;
        ?>
            <div class="day">
                <div class="day-header">
                    <span>Giorno <?= $giorno['day'] ?> · <?= $nomiGiorni[$idx] ?? '' ?></span>
                    <span class="day-totals">
                        <?= round($giorno['totals']['calories'] ?? 0) ?> kcal · 
                        <?= round($giorno['totals']['protein_g'] ?? 0) ?> g prot · 
                        <?= round($giorno['totals']['fat_g'] ?? 0) ?> g grassi · 
                        <?= round($giorno['totals']['carbs_g'] ?? 0) ?> g carb · 
                        <?= number_format($giorno['totals']['fiber_g'] ?? 0, 1) ?> g fibre · 
                        <?= number_format($giorno['totals']['sugar_g'] ?? 0, 1) ?> g zuccheri · 
                        <?= number_format($giorno['totals']['saturated_fat_g'] ?? 0, 1) ?> g grassi saturi
                    </span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Pasto</th>
                            <th>Ricetta</th>
                            <th>Calorie</th>
                            <th>Prot (g)</th>
                            <th>Grassi (g)</th>
                            <th>Carb (g)</th>
                            <th>Fibre (g)</th>
                            <th>Zuccheri (g)</th>
                            <th>Grassi saturi (g)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($giorno['meals'] as $pasto): 
                            $hasError = isset($pasto['error']) || !isset($pasto['recipe']);
                            $recipeName = $pasto['recipe'] ?? ($pasto['error'] ?? 'Nessuna ricetta disponibile');
                            $calories = isset($pasto['calories']) ? round($pasto['calories']) : '-';
                            $protein  = isset($pasto['protein_g']) ? round($pasto['protein_g']) : '-';
                            $fat      = isset($pasto['fat_g']) ? round($pasto['fat_g']) : '-';
                            $carbs    = isset($pasto['carbs_g']) ? round($pasto['carbs_g']) : '-';
                            $fiber    = isset($pasto['fiber_g']) ? number_format($pasto['fiber_g'], 1) : '-';
                            $sugar    = isset($pasto['sugar_g']) ? number_format($pasto['sugar_g'], 1) : '-';
                            $satFat   = isset($pasto['saturated_fat_g']) ? number_format($pasto['saturated_fat_g'], 1) : '-';
                            $mealLabel = $labelPasti[$pasto['meal']] ?? ucfirst($pasto['meal'] ?? 'Pasti');
                            $tagClass = $hasError ? 'meal-tag meal-tag-warning' : 'meal-tag';
                        ?>
                            <tr>
                                <td><span class="<?= $tagClass ?>"><?= htmlspecialchars($mealLabel) ?></span></td>
                                <td><?= htmlspecialchars($recipeName) ?></td>
                                <td><?= $calories ?></td>
                                <td><?= $protein ?></td>
                                <td><?= $fat ?></td>
                                <td><?= $carbs ?></td>
                                <td><?= $fiber ?></td>
                                <td><?= $sugar ?></td>
                                <td><?= $satFat ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>

        <details>
            <summary>Mostra JSON</summary>
            <pre><?= htmlspecialchars(json_encode($dati, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
        </details>

    <?php elseif ($inviato && !$dati): ?>
        <div class="alert alert-error">
            <strong>Nessuna risposta dal server</strong><br>
            Riprova più tardi.
        </div>
    <?php endif; ?>

    <footer>
        Basato su USDA FoodData Central · Dati nutrizionali: proteine, grassi, carboidrati, fibre, zuccheri, grassi saturi
    </footer>
</div>

<script>
const container = document.getElementById('allergyGroup');
const hiddenField = document.getElementById('allergiesField');

function aggiornaAllergie() {
    const check = container.querySelectorAll('input[type="checkbox"]:checked');
    const valori = Array.from(check).map(cb => cb.value);
    hiddenField.value = valori.join(',');
}
container.querySelectorAll('input[type="checkbox"]').forEach(cb => {
    cb.addEventListener('change', aggiornaAllergie);
});
aggiornaAllergie();

const form = document.getElementById('plannerForm');
const btn = document.getElementById('submitBtn');
if (form) {
    form.addEventListener('submit', function() {
        if (btn) {
            btn.innerHTML = '<span class="loader"></span>Generazione...';
            btn.disabled = true;
        }
    });
}
</script>
</body>
</html>