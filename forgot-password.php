<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/vendor/autoload.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));

    // Keep the response identical to avoid revealing registered accounts.
    $genericMessage =
        'If an account exists with that email, reset instructions will be sent.';

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            $pdo = db();

            $stmt = $pdo->prepare(
                'SELECT id, email FROM users WHERE email = :email LIMIT 1'
            );

            $stmt->execute([':email' => $email]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Remove existing reset tokens for this account.
                $delete = $pdo->prepare(
                    'DELETE FROM password_resets WHERE user_id = :user_id'
                );

                $delete->execute([
                    ':user_id' => $user['id'],
                ]);

                // Generate a cryptographically secure token.
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);

                // Store only the token hash; expire it after 30 minutes.
                $insert = $pdo->prepare(
                    'INSERT INTO password_resets
                        (user_id, token_hash, expires_at)
                     VALUES
                        (
                            :user_id,
                            :token_hash,
                            DATE_ADD(NOW(), INTERVAL 30 MINUTE)
                        )'
                );

                $insert->execute([
                    ':user_id' => $user['id'],
                    ':token_hash' => $tokenHash,
                ]);

                // Local development URL.
                // Replace this with your HTTPS domain when deploying.
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                    ? 'https'
                    : 'http';
                $host = (string) ($_SERVER['SERVER_NAME'] ?? 'localhost');
                $port = (int) ($_SERVER['SERVER_PORT'] ?? 0);
                if ($port > 0 && !in_array($port, [80, 443], true)) {
                    $host .= ':' . $port;
                }
                $resetUrl = $scheme . '://' . $host
                    . app_url('reset-password.php?token=' . urlencode($token));

                $subject = 'Reset your examspace password';

                $body = "Hello,\n\n"
                    . "We received a request to reset your examspace password.\n\n"
                    . "Click the link below to reset your password. "
                    . "This link expires in 30 minutes:\n\n"
                    . $resetUrl . "\n\n"
                    . "If you did not request this, you can ignore this email.\n\n"
                    . "Regards,\n"
                    . "The examspace Team";

                try {
                    // Load SMTP credentials from config/mail.php.
                    $mailConfig = require __DIR__ . '/config/mail.php';

                    $mail = new PHPMailer(true);

                    // Configure Gmail SMTP.
                    $mail->isSMTP();
                    $mail->Host = $mailConfig['host'];
                    $mail->SMTPAuth = true;
                    $mail->Username = $mailConfig['username'];
                    $mail->Password = $mailConfig['password'];
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = (int) $mailConfig['port'];

                    // Configure sender and recipient.
                    $mail->CharSet = 'UTF-8';

                    $mail->setFrom(
                        $mailConfig['from_email'],
                        $mailConfig['from_name']
                    );

                    $mail->addAddress($user['email']);

                    // Configure message content.
                    $mail->Subject = $subject;
                    $mail->Body = $body;
                    $mail->isHTML(false);

                    // Send the email.
                    $mail->send();

                } catch (Exception $exception) {
                    // Never expose SMTP details or credentials to the user.
                    error_log(
                        'Password reset email failed for user ID '
                        . (int) $user['id']
                        . ': '
                        . $exception->getMessage()
                    );

                    // Remove the unusable token if delivery failed.
                    $removeToken = $pdo->prepare(
                        'DELETE FROM password_resets
                         WHERE user_id = :user_id
                           AND token_hash = :token_hash'
                    );

                    $removeToken->execute([
                        ':user_id' => $user['id'],
                        ':token_hash' => $tokenHash,
                    ]);
                }
            }
        } catch (Throwable $exception) {
            // Log server-side errors without revealing account information.
            error_log(
                'Forgot-password processing failed: '
                . $exception->getMessage()
            );
        }
    }

    $message = $genericMessage;
}

$pageTitle = 'Forgot Password';
$basePath = app_url('');

require_once __DIR__ . '/includes/header.php';
?>

<section class="forgot-page">
    <section class="forgot-card">

        <h1>Forgot password?</h1>

        <p>
            Enter the email address associated with your account.
            If an account exists, we will send you instructions to
            reset your password.
        </p>

        <?php if ($message !== ''): ?>
            <div class="forgot-message" role="status">
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="forgot-password.php">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <label for="email">Email address</label>

            <input
                type="email"
                id="email"
                name="email"
                placeholder="you@example.com"
                autocomplete="email"
                maxlength="190"
                required
            >

            <button type="submit" class="forgot-button">
                Send reset instructions
            </button>

        </form>

        <a class="back-to-login" href="login.php">
            Back to login
        </a>

    </section>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>