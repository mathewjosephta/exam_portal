<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/includes/functions.php';

$title = 'Forgot Password';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-shell">
    <div class="auth-card">
        <h1>Reset password</h1>
        <p class="muted">This local project does not provide a public password reset form. Use a trusted admin or direct database update to reset a user password securely.</p>
        <a class="button" href="<?= e(app_url('login.php')) ?>">Back to login</a>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
