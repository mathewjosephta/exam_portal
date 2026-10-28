<?php
declare(strict_types=1);

require __DIR__ . '/config/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Sign out must be submitted as a form.');
}
verify_csrf($_POST['csrf_token'] ?? null);
$_SESSION = [];
session_regenerate_id(true);
session_destroy();
header('Location: index.html', true, 302);
exit;
