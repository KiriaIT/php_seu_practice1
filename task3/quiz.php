<?php
/**
 * Task 3 — Student quiz.
 * Form data is processed via $_POST. 5 questions: 3 multiple-choice
 * (4 options, exactly one correct) and 2 open-ended (free-text answer).
 */

const TYPE_TEST = 'test';
const TYPE_OPEN = 'open';

/**
 * Correct answers live only on the server and are never rendered into the form.
 *  - test: 'options' are the four choices, 'correct' is the key of the right one;
 *  - open: 'answers' lists accepted answers (normalized), 'display' is shown in the results.
 */
const QUESTIONS = [
    1 => [
        'type'    => TYPE_TEST,
        'text'    => 'რას ნიშნავს აბრევიატურა PHP?',
        'options' => [
            'a' => 'Programming Home Pages',
            'b' => 'PHP: Hypertext Preprocessor',
            'c' => 'Private Hypertext Processor',
            'd' => 'Preprocessed Hypertext Pages',
        ],
        'correct' => 'b',
    ],
    2 => [
        'type'    => TYPE_TEST,
        'text'    => 'რომელი სიმბოლოთი იწყება ცვლადის სახელი PHP-ში?',
        'options' => [
            'a' => '#',
            'b' => '&',
            'c' => '$',
            'd' => '@',
        ],
        'correct' => 'c',
    ],
    3 => [
        'type'    => TYPE_TEST,
        'text'    => 'ფორმის გაგზავნის რომელი მეთოდის დროს ჩანს მონაცემები ბრაუზერის მისამართის ზოლში (URL-ში)?',
        'options' => [
            'a' => 'POST',
            'b' => 'PUT',
            'c' => 'DELETE',
            'd' => 'GET',
        ],
        'correct' => 'd',
    ],
    4 => [
        'type'    => TYPE_OPEN,
        'text'    => 'რომელი ფუნქცია აბრუნებს მასივის ელემენტების რაოდენობას?',
        'answers' => ['count', 'sizeof'],
        'display' => 'count() (ან მისი ფსევდონიმი sizeof())',
    ],
    5 => [
        'type'    => TYPE_OPEN,
        'text'    => 'რომელი კონსტრუქციით (ფუნქციით) მოწმდება, არსებობს თუ არა ცვლადი და განსხვავდება თუ არა მისი მნიშვნელობა null-ისგან? (მაგ.: ფორმის ველის შემოწმებისას)',
        'answers' => ['isset'],
        'display' => 'isset()',
    ],
];

/** Escapes a value for safe HTML output (XSS protection). */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Normalizes an open answer before comparison: lowercased (PHP function names
 * are case-insensitive), whitespace removed, trailing ";" and "()" stripped.
 */
function normalizeAnswer(string $answer): string
{
    $answer = function_exists('mb_strtolower') ? mb_strtolower($answer, 'UTF-8') : strtolower($answer);
    $answer = preg_replace('/\s+/u', '', $answer) ?? '';
    $answer = rtrim($answer, ';');

    if (substr($answer, -2) === '()') {
        $answer = substr($answer, 0, -2);
    }

    return $answer;
}

function isCorrect(array $question, string $answer): bool
{
    if ($question['type'] === TYPE_TEST) {
        return $answer === $question['correct'];
    }

    return in_array(normalizeAnswer($answer), $question['answers'], true);
}

$isSubmitted = $_SERVER['REQUEST_METHOD'] === 'POST';
$results = [];
$correctCount = 0;
$total = count(QUESTIONS);

if ($isSubmitted) {
    $postedAnswers = isset($_POST['answers']) && is_array($_POST['answers']) ? $_POST['answers'] : [];

    foreach (QUESTIONS as $number => $question) {
        $answer = $postedAnswers[$number] ?? '';
        $answer = is_string($answer) ? trim($answer) : '';

        // Anything other than one of the offered options (a–d) counts as unanswered.
        if ($question['type'] === TYPE_TEST && !array_key_exists($answer, $question['options'])) {
            $answer = '';
        }

        $correct = $answer !== '' && isCorrect($question, $answer);
        if ($correct) {
            $correctCount++;
        }

        $results[$number] = [
            'answer'  => $answer,
            'correct' => $correct,
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>სტუდენტის შემოწმების ტესტი</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container">
    <a class="back-link" href="../index.php">&larr; ყველა დავალება</a>

    <header class="page-header">
        <h1>სტუდენტის შემოწმების ტესტი</h1>
        <p>დავალება 3 — <?= $total ?> შეკითხვა: 3 ტესტური და 2 ღია. მეთოდი: <code>POST</code></p>
    </header>

    <?php if ($isSubmitted): ?>
        <section class="card">
            <h2>შედეგი</h2>
            <p class="score">სწორი პასუხების რაოდენობა: <?= $correctCount ?>/<?= $total ?></p>

            <div class="table-wrap">
                <table>
                    <caption>პასუხების შემოწმება</caption>
                    <thead>
                    <tr>
                        <th class="center">№</th>
                        <th>შეკითხვა</th>
                        <th>ტიპი</th>
                        <th>თქვენი პასუხი</th>
                        <th>სწორი პასუხი</th>
                        <th class="center">შედეგი</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach (QUESTIONS as $number => $question): ?>
                        <?php
                        $answer = $results[$number]['answer'];
                        if ($question['type'] === TYPE_TEST) {
                            $typeLabel     = 'ტესტური';
                            $studentAnswer = $answer !== '' ? $answer . ') ' . $question['options'][$answer] : '';
                            $correctAnswer = $question['correct'] . ') ' . $question['options'][$question['correct']];
                        } else {
                            $typeLabel     = 'ღია';
                            $studentAnswer = $answer;
                            $correctAnswer = $question['display'];
                        }
                        ?>
                        <tr>
                            <td class="center"><?= $number ?></td>
                            <td><?= e($question['text']) ?></td>
                            <td><?= $typeLabel ?></td>
                            <td><?= $studentAnswer !== '' ? e($studentAnswer) : '<em>პასუხი არ არის</em>' ?></td>
                            <td><?= e($correctAnswer) ?></td>
                            <td class="center">
                                <?php if ($results[$number]['correct']): ?>
                                    <span class="ok">✔ სწორია</span>
                                <?php else: ?>
                                    <span class="wrong">✘ არასწორია</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="actions" style="margin-top: 18px;">
                <a class="btn" href="quiz.php">ტესტის თავიდან გავლა</a>
            </div>
        </section>
    <?php else: ?>
        <section class="card">
            <h2>უპასუხეთ შეკითხვებს</h2>
            <p class="hint">ტესტურ შეკითხვაზე აირჩიეთ ერთი პასუხი, ღია შეკითხვაზე პასუხი ჩაწერეთ ველში.</p>

            <form method="post" action="quiz.php">
                <?php foreach (QUESTIONS as $number => $question): ?>
                    <div class="question">
                        <span class="question-type"><?= $question['type'] === TYPE_TEST ? 'ტესტური შეკითხვა' : 'ღია შეკითხვა' ?></span>
                        <p class="question-title"><?= $number ?>. <?= e($question['text']) ?></p>

                        <?php if ($question['type'] === TYPE_TEST): ?>
                            <?php foreach ($question['options'] as $key => $option): ?>
                                <label class="option">
                                    <input type="radio" name="answers[<?= $number ?>]" value="<?= e($key) ?>">
                                    <?= e($key) ?>) <?= e($option) ?>
                                </label>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <label class="visually-hidden" for="answer_<?= $number ?>">პასუხი</label>
                            <input type="text" id="answer_<?= $number ?>" name="answers[<?= $number ?>]"
                                   maxlength="100" placeholder="ჩაწერეთ პასუხი" autocomplete="off">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div class="actions">
                    <button type="submit" class="btn">პასუხების გაგზავნა</button>
                    <button type="reset" class="btn btn-secondary">გასუფთავება</button>
                </div>
            </form>
        </section>
    <?php endif; ?>
</div>
</body>
</html>
