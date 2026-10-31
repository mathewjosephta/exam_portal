<?php
declare(strict_types=1);

$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params([
    'httponly' => true,
    'path' => '/',
    'secure' => $isHttps,
    'samesite' => 'Lax',
]);
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

$databaseConfig = __DIR__ . '/database.php';
if (!is_file($databaseConfig)) {
    http_response_code(500);
    exit('Database is not configured. Copy config/database.example.php to config/database.php and update its credentials.');
}

$pdo = require $databaseConfig;
if (!$pdo instanceof PDO) {
    http_response_code(500);
    exit('Database configuration is invalid. Copy config/database.example.php to config/database.php and configure the database credentials.');
}
date_default_timezone_set('UTC');
$pdo->exec("SET time_zone = '+00:00'");

function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_url(string $path = ''): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $basePath = preg_replace('~/(admin|student|api)/[^/]+$~', '', $script);
    $basePath = preg_replace('~/[^/]+\.php$~', '', $basePath ?? '');
    return rtrim($basePath ?? '', '/') . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . app_url($path));
    exit;
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(mixed $token): void
{
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        exit('Your session expired. Reload the page and try again.');
    }
}

function request_string(array $source, string $key): string
{
    return isset($source[$key]) && is_string($source[$key]) ? $source[$key] : '';
}

function require_role(string $role): void
{
    if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
        flash('error', 'Please sign in to continue.');
        redirect('login.php');
    }
    if ($_SESSION['role'] !== $role) {
        redirect($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function render_header(string $title): void
{
    $userName = $_SESSION['user_name'] ?? null;
    $role = $_SESSION['role'] ?? null;
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> | ExamPortal</title>
        <link rel="stylesheet" href="<?= e(app_url('assets/css/style.css')) ?>">
    </head>
    <body>
    <header class="site-header">
        <div class="container nav">
            <a class="brand" href="<?= e(app_url('index.php')) ?>">Exam<span>Portal</span></a>
            <nav class="nav-actions" aria-label="Main navigation">
                <?php if ($role === 'admin'): ?>
                    <a href="<?= e(app_url('admin/dashboard.php')) ?>">Dashboard</a>
                    <a href="<?= e(app_url('admin/exams.php')) ?>">Exams</a>
                    <a href="<?= e(app_url('admin/results.php')) ?>">Results</a>
                <?php elseif ($role === 'student'): ?>
                    <a href="<?= e(app_url('student/dashboard.php')) ?>">Dashboard</a>
                    <a href="<?= e(app_url('student/results.php')) ?>">My results</a>
                <?php endif; ?>
                <?php if ($userName !== null): ?>
                    <span class="nav-user"><?= e($userName) ?></span>
                    <form class="inline-form" method="post" action="<?= e(app_url('logout.php')) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <button class="link-button" type="submit">Sign out</button>
                    </form>
                <?php else: ?>
                    <a href="<?= e(app_url('login.php')) ?>">Sign in</a>
                    <a class="button button-small" href="<?= e(app_url('register.php')) ?>">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="container page-content">
        <?php if ($message !== null): ?>
            <div class="notice notice-<?= e($message['type']) ?>" role="status"><?= e($message['message']) ?></div>
        <?php endif; ?>
    <?php
}

function render_footer(): void
{
    ?>
    </main>
    <footer class="site-footer">
        <div class="container">&copy; <?= date('Y') ?> ExamPortal</div>
    </footer>
    </body>
    </html>
    <?php
}
