<?php
/**
 * Task 1 (a) — Payroll sheet.
 * Form data is processed via $_GET; income tax is fixed at 20%.
 */

const TAX_RATE = 20; // percent

/** Escapes a value for safe HTML output (XSS protection). */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Returns a trimmed field from $_GET; non-string input (e.g. an array) becomes ''. */
function getField(string $name): string
{
    $value = $_GET[$name] ?? '';
    return is_string($value) ? trim($value) : '';
}

function money(float $amount): string
{
    return number_format($amount, 2, '.', ' ') . ' ₾';
}

/** Letters, spaces, hyphens and apostrophes only; up to 50 characters. */
function isValidName(string $name): bool
{
    return preg_match("/^\p{L}[\p{L} '-]{0,49}$/u", $name) === 1;
}

$firstName = getField('first_name');
$lastName  = getField('last_name');
$position  = getField('position');
$salaryRaw = getField('salary');

$isSubmitted = isset($_GET['calculate']);
$errors = [];
$result = null;

if ($isSubmitted) {
    if ($firstName === '') {
        $errors[] = 'შეიყვანეთ სახელი.';
    } elseif (!isValidName($firstName)) {
        $errors[] = 'სახელი უნდა შეიცავდეს მხოლოდ ასოებს (მაქსიმუმ 50 სიმბოლო).';
    }

    if ($lastName === '') {
        $errors[] = 'შეიყვანეთ გვარი.';
    } elseif (!isValidName($lastName)) {
        $errors[] = 'გვარი უნდა შეიცავდეს მხოლოდ ასოებს (მაქსიმუმ 50 სიმბოლო).';
    }

    if ($position === '') {
        $errors[] = 'შეიყვანეთ დაკავებული თანამდებობა.';
    }

    $salary = filter_var(str_replace(',', '.', $salaryRaw), FILTER_VALIDATE_FLOAT);
    if ($salaryRaw === '') {
        $errors[] = 'შეიყვანეთ ხელფასის რაოდენობა.';
    } elseif ($salary === false || !is_finite($salary) || $salary <= 0) {
        $errors[] = 'ხელფასის რაოდენობა უნდა იყოს დადებითი რიცხვი.';
    } elseif ($salary > 1000000000) {
        $errors[] = 'ხელფასის რაოდენობა ძალიან დიდია.';
    }

    if (empty($errors)) {
        $salary    = round($salary, 2);
        $tax       = round($salary * TAX_RATE / 100, 2);
        $netSalary = round($salary - $tax, 2);

        $result = [
            'salary'     => $salary,
            'tax'        => $tax,
            'net_salary' => $netSalary,
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>სახელფასო უწყისი — ფიქსირებული გადასახადი 20%</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container">
    <a class="back-link" href="../index.php">&larr; ყველა დავალება</a>

    <header class="page-header">
        <h1>სახელფასო უწყისი</h1>
        <p>დავალება 1 (ა) — საშემოსავლო გადასახადი ფიქსირებულია: <strong><?= TAX_RATE ?>%</strong>. მეთოდი: <code>GET</code></p>
    </header>

    <section class="card">
        <h2>თანამშრომლის მონაცემები</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <strong>ფორმა შევსებულია არასწორად:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="get" action="fixed.php">
            <div class="form-grid">
                <div class="form-group">
                    <label for="first_name">სახელი <span class="required">*</span></label>
                    <input type="text" id="first_name" name="first_name" maxlength="50" required
                           value="<?= e($firstName) ?>">
                </div>
                <div class="form-group">
                    <label for="last_name">გვარი <span class="required">*</span></label>
                    <input type="text" id="last_name" name="last_name" maxlength="50" required
                           value="<?= e($lastName) ?>">
                </div>
                <div class="form-group">
                    <label for="position">დაკავებული თანამდებობა <span class="required">*</span></label>
                    <input type="text" id="position" name="position" maxlength="100" required
                           value="<?= e($position) ?>">
                </div>
                <div class="form-group">
                    <label for="salary">ხელფასის რაოდენობა, ₾ <span class="required">*</span></label>
                    <input type="number" id="salary" name="salary" min="0.01" step="0.01" required
                           value="<?= e($salaryRaw) ?>">
                    <p class="hint">დარიცხული ხელფასი (გადასახადის გამოქვითვამდე)</p>
                </div>
            </div>

            <p class="hint">საშემოსავლო გადასახადი: <strong><?= TAX_RATE ?>%</strong> (ფიქსირებული)</p>

            <div class="actions">
                <button type="submit" class="btn" name="calculate" value="1">გამოთვლა</button>
                <a class="btn btn-secondary" href="fixed.php">გასუფთავება</a>
            </div>
        </form>
    </section>

    <?php if ($result !== null): ?>
        <section class="card">
            <div class="table-wrap">
                <table>
                    <caption>სახელფასო უწყისი (საშემოსავლო გადასახადი — <?= TAX_RATE ?>%)</caption>
                    <thead>
                    <tr>
                        <th>№</th>
                        <th>სახელი</th>
                        <th>გვარი</th>
                        <th>დაკავებული თანამდებობა</th>
                        <th class="num">დარიცხული ხელფასი</th>
                        <th class="num">გადასახადის განაკვეთი</th>
                        <th class="num">საშემოსავლო გადასახადი</th>
                        <th class="num">ხელზე ასაღები ხელფასი</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>1</td>
                        <td><?= e($firstName) ?></td>
                        <td><?= e($lastName) ?></td>
                        <td><?= e($position) ?></td>
                        <td class="num"><?= money($result['salary']) ?></td>
                        <td class="num"><?= TAX_RATE ?>%</td>
                        <td class="num"><?= money($result['tax']) ?></td>
                        <td class="num"><strong><?= money($result['net_salary']) ?></strong></td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <p class="hint">
                გაანგარიშება: <?= money($result['salary']) ?> × <?= TAX_RATE ?>% = <?= money($result['tax']) ?> (გადასახადი);
                <?= money($result['salary']) ?> − <?= money($result['tax']) ?> = <?= money($result['net_salary']) ?> (ხელზე ასაღები).
            </p>
        </section>
    <?php endif; ?>
</div>
</body>
</html>
