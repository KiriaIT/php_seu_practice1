<?php
/**
 * Task 2 — Grade sheet.
 * Form data is processed via $_POST; the 0–100 score is mapped to a letter grade
 * (A–E are passing grades, FX and F are failing).
 */

// Georgian higher-education grading scale (Order of the Minister of Education No. 3, 5 January 2007).
const GRADE_SCALE = [
    ['letter' => 'A',  'min' => 91, 'max' => 100, 'label' => 'ფრიადი',             'passed' => true],
    ['letter' => 'B',  'min' => 81, 'max' => 90,  'label' => 'ძალიან კარგი',       'passed' => true],
    ['letter' => 'C',  'min' => 71, 'max' => 80,  'label' => 'კარგი',              'passed' => true],
    ['letter' => 'D',  'min' => 61, 'max' => 70,  'label' => 'დამაკმაყოფილებელი', 'passed' => true],
    ['letter' => 'E',  'min' => 51, 'max' => 60,  'label' => 'საკმარისი',          'passed' => true],
    ['letter' => 'FX', 'min' => 41, 'max' => 50,  'label' => 'ვერ ჩააბარა',        'passed' => false,
        'note' => 'სტუდენტს უფლება აქვს გავიდეს დამატებით გამოცდაზე.'],
    ['letter' => 'F',  'min' => 0,  'max' => 40,  'label' => 'ჩაიჭრა',             'passed' => false,
        'note' => 'სტუდენტს საგანი ახლიდან აქვს შესასწავლი.'],
];

const COURSES   = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'];
const SEMESTERS = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII'];

/** Escapes a value for safe HTML output (XSS protection). */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Returns a trimmed field from $_POST; non-string input (e.g. an array) becomes ''. */
function postField(string $name): string
{
    $value = $_POST[$name] ?? '';
    return is_string($value) ? trim($value) : '';
}

/** Letters, spaces, hyphens and apostrophes only; up to 50 characters. */
function isValidName(string $name): bool
{
    return preg_match("/^\p{L}[\p{L} '-]{0,49}$/u", $name) === 1;
}

function getGrade(int $score): array
{
    foreach (GRADE_SCALE as $grade) {
        if ($score >= $grade['min'] && $score <= $grade['max']) {
            return $grade;
        }
    }

    throw new InvalidArgumentException('Score must be between 0 and 100.');
}

// Field name => label used in error messages.
$nameFields = [
    'student_first_name'  => 'სტუდენტის სახელი',
    'student_last_name'   => 'სტუდენტის გვარი',
    'lecturer_first_name' => 'ლექტორის სახელი',
    'lecturer_last_name'  => 'ლექტორის გვარი',
    'dean_first_name'     => 'დეკანის სახელი',
    'dean_last_name'      => 'დეკანის გვარი',
];

$data = [];
foreach (array_merge(array_keys($nameFields), ['course', 'semester', 'subject', 'score']) as $field) {
    $data[$field] = postField($field);
}

$isSubmitted = $_SERVER['REQUEST_METHOD'] === 'POST';
$errors = [];
$result = null;

if ($isSubmitted) {
    foreach ($nameFields as $field => $title) {
        if ($data[$field] === '') {
            $errors[] = "შეიყვანეთ: {$title}.";
        } elseif (!isValidName($data[$field])) {
            $errors[] = "ველი „{$title}“ უნდა შეიცავდეს მხოლოდ ასოებს (მაქსიმუმ 50 სიმბოლო).";
        }
    }

    $course = filter_var($data['course'], FILTER_VALIDATE_INT);
    if ($data['course'] === '') {
        $errors[] = 'აირჩიეთ კურსი.';
    } elseif ($course === false || !array_key_exists($course, COURSES)) {
        $errors[] = 'კურსი არასწორია.';
        $course = false;
    }

    $semester = filter_var($data['semester'], FILTER_VALIDATE_INT);
    if ($data['semester'] === '') {
        $errors[] = 'აირჩიეთ სემესტრი.';
    } elseif ($semester === false || !array_key_exists($semester, SEMESTERS)) {
        $errors[] = 'სემესტრი არასწორია.';
        $semester = false;
    }

    // Year N of study covers semesters 2N-1 and 2N.
    if ($course !== false && $semester !== false && (int) ceil($semester / 2) !== $course) {
        $errors[] = sprintf(
            '%s კურსს შეესაბამება %s ან %s სემესტრი.',
            COURSES[$course],
            SEMESTERS[$course * 2 - 1],
            SEMESTERS[$course * 2]
        );
    }

    if ($data['subject'] === '') {
        $errors[] = 'შეიყვანეთ სასწავლო კურსი.';
    }

    $score = filter_var($data['score'], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 0, 'max_range' => 100],
    ]);
    if ($data['score'] === '') {
        $errors[] = 'შეიყვანეთ მიღებული ქულა.';
    } elseif ($score === false) {
        $errors[] = 'ქულა უნდა იყოს მთელი რიცხვი 0-დან 100-მდე.';
    }

    if (empty($errors)) {
        $result = [
            'course'   => $course,
            'semester' => $semester,
            'score'    => $score,
            'grade'    => getGrade($score),
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ნიშნების უწყისი</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container">
    <a class="back-link" href="../index.php">&larr; ყველა დავალება</a>

    <header class="page-header">
        <h1>ნიშნების უწყისი</h1>
        <p>დავალება 2 — ქულის მიხედვით შეფასების განსაზღვრა. მეთოდი: <code>POST</code></p>
    </header>

    <section class="card">
        <h2>უწყისის შევსება</h2>

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

        <form method="post" action="grades.php">
            <fieldset>
                <legend>სტუდენტი</legend>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="student_first_name">სახელი <span class="required">*</span></label>
                        <input type="text" id="student_first_name" name="student_first_name" maxlength="50" required
                               value="<?= e($data['student_first_name']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="student_last_name">გვარი <span class="required">*</span></label>
                        <input type="text" id="student_last_name" name="student_last_name" maxlength="50" required
                               value="<?= e($data['student_last_name']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="course">კურსი <span class="required">*</span></label>
                        <select id="course" name="course" required>
                            <option value="">— აირჩიეთ —</option>
                            <?php foreach (COURSES as $value => $roman): ?>
                                <option value="<?= $value ?>"<?= $data['course'] === (string) $value ? ' selected' : '' ?>><?= $roman ?> კურსი</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="semester">სემესტრი <span class="required">*</span></label>
                        <select id="semester" name="semester" required>
                            <option value="">— აირჩიეთ —</option>
                            <?php foreach (SEMESTERS as $value => $roman): ?>
                                <option value="<?= $value ?>"<?= $data['semester'] === (string) $value ? ' selected' : '' ?>><?= $roman ?> სემესტრი</option>
                            <?php endforeach; ?>
                        </select>
                        <p class="hint">N კურსს შეესაბამება (2N−1) და 2N სემესტრი</p>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>სასწავლო კურსი და შეფასება</legend>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="subject">სასწავლო კურსი <span class="required">*</span></label>
                        <input type="text" id="subject" name="subject" maxlength="100" required
                               value="<?= e($data['subject']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="score">მიღებული ქულა (0–100) <span class="required">*</span></label>
                        <input type="number" id="score" name="score" min="0" max="100" step="1" required
                               value="<?= e($data['score']) ?>">
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>ლექტორი</legend>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="lecturer_first_name">სახელი <span class="required">*</span></label>
                        <input type="text" id="lecturer_first_name" name="lecturer_first_name" maxlength="50" required
                               value="<?= e($data['lecturer_first_name']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="lecturer_last_name">გვარი <span class="required">*</span></label>
                        <input type="text" id="lecturer_last_name" name="lecturer_last_name" maxlength="50" required
                               value="<?= e($data['lecturer_last_name']) ?>">
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>დეკანი</legend>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="dean_first_name">სახელი <span class="required">*</span></label>
                        <input type="text" id="dean_first_name" name="dean_first_name" maxlength="50" required
                               value="<?= e($data['dean_first_name']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="dean_last_name">გვარი <span class="required">*</span></label>
                        <input type="text" id="dean_last_name" name="dean_last_name" maxlength="50" required
                               value="<?= e($data['dean_last_name']) ?>">
                    </div>
                </div>
            </fieldset>

            <div class="actions">
                <button type="submit" class="btn">შეფასების განსაზღვრა</button>
                <a class="btn btn-secondary" href="grades.php">გასუფთავება</a>
            </div>
        </form>
    </section>

    <?php if ($result !== null): ?>
        <?php $grade = $result['grade']; ?>
        <section class="card">
            <div class="table-wrap">
                <table>
                    <caption>ნიშნების უწყისი</caption>
                    <thead>
                    <tr>
                        <th>დასახელება</th>
                        <th>მონაცემი</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <th>სტუდენტის სახელი</th>
                        <td><?= e($data['student_first_name']) ?></td>
                    </tr>
                    <tr>
                        <th>სტუდენტის გვარი</th>
                        <td><?= e($data['student_last_name']) ?></td>
                    </tr>
                    <tr>
                        <th>კურსი</th>
                        <td><?= COURSES[$result['course']] ?> კურსი</td>
                    </tr>
                    <tr>
                        <th>სემესტრი</th>
                        <td><?= SEMESTERS[$result['semester']] ?> სემესტრი</td>
                    </tr>
                    <tr>
                        <th>სასწავლო კურსი</th>
                        <td><?= e($data['subject']) ?></td>
                    </tr>
                    <tr>
                        <th>მიღებული ქულა</th>
                        <td><?= $result['score'] ?> / 100</td>
                    </tr>
                    <tr class="total">
                        <th>შეფასება</th>
                        <td>
                            <span class="badge<?= $grade['passed'] ? '' : ' fail' ?>"><?= $grade['letter'] ?></span>
                            — <?= e($grade['label']) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>შედეგი</th>
                        <td>
                            <?php if ($grade['passed']): ?>
                                <span class="ok">ჩააბარა</span> (დადებითი შეფასება)
                            <?php else: ?>
                                <span class="wrong">ვერ ჩააბარა</span> (უარყოფითი შეფასება).
                                <?= e($grade['note']) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>ლექტორი</th>
                        <td><?= e($data['lecturer_first_name'] . ' ' . $data['lecturer_last_name']) ?></td>
                    </tr>
                    <tr>
                        <th>დეკანი</th>
                        <td><?= e($data['dean_first_name'] . ' ' . $data['dean_last_name']) ?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <div class="table-wrap">
                <table>
                    <caption>შეფასების სისტემა</caption>
                    <thead>
                    <tr>
                        <th class="center">შეფასება</th>
                        <th>განმარტება</th>
                        <th class="center">ქულა</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach (GRADE_SCALE as $row): ?>
                        <tr<?= $row['letter'] === $grade['letter'] ? ' class="current"' : '' ?>>
                            <td class="center"><?= $row['letter'] ?></td>
                            <td><?= e($row['label']) ?></td>
                            <td class="center"><?= $row['min'] ?>–<?= $row['max'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</div>
</body>
</html>
