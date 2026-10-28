<?php
declare(strict_types=1);

$script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$base = preg_replace('~/(?:admin|student)/[^/]+$~', '', $script);
$base = $base === $script ? str_replace('\\', '/', dirname($script)) : $base;
$base = rtrim($base === '.' ? '' : ($base ?? ''), '/');
$relative = ltrim(substr($script, strlen($base)), '/');
$pages = [
    'login.php' => 'login.html',
    'register.php' => 'register.html',
    'student/dashboard.php' => 'student/dashboard.html',
    'student/exam-instructions.php' => 'student/exam-instructions.html',
    'student/take-exam.php' => 'student/take-exam.html',
    'student/submit-exam.php' => 'student/take-exam.html',
    'student/results.php' => 'student/results.html',
    'student/view-result.php' => 'student/view-result.html',
    'admin/dashboard.php' => 'admin/dashboard.html',
    'admin/exams.php' => 'admin/exams.html',
    'admin/create-exam.php' => 'admin/create-exam.html',
    'admin/questions.php' => 'admin/questions.html',
    'admin/add-question.php' => 'admin/add-question.html',
    'admin/results.php' => 'admin/results.html',
];
$destination = $pages[$relative] ?? 'index.html';
$query = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
    ? '?' . $_SERVER['QUERY_STRING']
    : '';

header('Location: ' . $base . '/' . $destination . $query, true, 302);
exit;
