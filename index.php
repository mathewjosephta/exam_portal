
```php
<?php
date_default_timezone_set('UTC');
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

if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
        exit;
    }

    if ($_SESSION['role'] === 'student') {
        header('Location: student/dashboard.php');
        exit;
    }
}

$loggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamPortal | Online Examination System</title>
    <meta name="description" content="A simple and secure online examination portal for students and administrators.">

    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --background: #f8fafc;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --white: #ffffff;
            --green: #16a34a;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: var(--background);
            color: var(--text);
            line-height: 1.6;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .container {
            width: min(1120px, 92%);
            margin: auto;
        }

        header {
            background: var(--white);
            border-bottom: 1px solid var(--border);
        }

        .navbar {
            min-height: 76px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .logo span {
            color: var(--primary);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .nav-links a {
            color: var(--muted);
            font-size: 14px;
            font-weight: 600;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            border: 1px solid transparent;
            border-radius: 8px;
            background: var(--primary);
            color: var(--white) !important;
            font-size: 14px;
            font-weight: 700;
            transition: background 0.2s, transform 0.2s;
        }

        .button:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .button-outline {
            background: var(--white);
            color: var(--primary) !important;
            border-color: var(--border);
        }

        .button-outline:hover {
            background: #eff6ff;
        }

        .hero {
            padding: 88px 0 76px;
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 7px 13px;
            border-radius: 999px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 24px;
        }

        .hero h1 {
            max-width: 800px;
            margin: 0 auto 22px;
            font-size: clamp(36px, 6vw, 62px);
            line-height: 1.12;
            letter-spacing: -2px;
        }

        .hero h1 span {
            color: var(--primary);
        }

        .hero p {
            max-width: 650px;
            margin: 0 auto 30px;
            color: var(--muted);
            font-size: 17px;
        }

        .hero-actions {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .features {
            padding: 30px 0 80px;
        }

        .section-heading {
            margin-bottom: 35px;
            text-align: center;
        }

        .section-heading h2 {
            font-size: 32px;
            margin-bottom: 10px;
            letter-spacing: -1px;
        }

        .section-heading p {
            color: var(--muted);
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
        }

        .feature-card {
            padding: 28px;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: var(--white);
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            margin-bottom: 20px;
            border-radius: 10px;
            background: #eff6ff;
            color: var(--primary);
            font-size: 23px;
            font-weight: 700;
        }

        .feature-card h3 {
            margin-bottom: 10px;
            font-size: 18px;
        }

        .feature-card p {
            color: var(--muted);
            font-size: 14px;
        }

        .how-it-works {
            padding: 65px 0;
            background: var(--white);
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        .steps {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 35px;
        }

        .step {
            text-align: center;
            padding: 20px;
        }

        .step-number {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            margin: 0 auto 15px;
            border-radius: 50%;
            background: var(--primary);
            color: var(--white);
            font-weight: 800;
        }

        .step h3 {
            margin-bottom: 8px;
        }

        .step p {
            color: var(--muted);
            font-size: 14px;
        }

        .cta {
            padding: 70px 0;
            text-align: center;
        }

        .cta h2 {
            margin-bottom: 12px;
            font-size: 32px;
        }

        .cta p {
            margin-bottom: 24px;
            color: var(--muted);
        }

        footer {
            padding: 25px 0;
            background: var(--white);
            border-top: 1px solid var(--border);
            color: var(--muted);
            font-size: 13px;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        @media (max-width: 760px) {
            .navbar {
                padding: 18px 0;
                flex-wrap: wrap;
            }

            .nav-links {
                gap: 14px;
                flex-wrap: wrap;
            }

            .hero {
                padding: 65px 0;
            }

            .feature-grid,
            .steps {
                grid-template-columns: 1fr;
            }

            .section-heading h2,
            .cta h2 {
                font-size: 27px;
            }

            .footer-content {
                justify-content: center;
                text-align: center;
            }
        }
    </style>
</head>

<body>

<header>
    <div class="container navbar">
        <a class="logo" href="index.php">Exam<span>Portal</span></a>

        <nav class="nav-links" aria-label="Main navigation">
            <a href="#features">Features</a>
            <a href="#how-it-works">How It Works</a>
            <a href="login.php">Login</a>
            <a class="button" href="register.php">Get Started</a>
        </nav>
    </div>
</header>

<main>
    <section class="hero">
        <div class="container">
            <span class="badge">ONLINE EXAMINATION SYSTEM</span>

            <h1>
                A smarter way to <span>take exams.</span>
            </h1>

            <p>
                Take online examinations in a simple, organized environment.
                Access available exams, answer questions, and review your results
                from one convenient platform.
            </p>

            <div class="hero-actions">
                <a class="button" href="register.php">Create Student Account</a>
                <a class="button button-outline" href="login.php">Sign In</a>
            </div>
        </div>
    </section>

    <section class="features" id="features">
        <div class="container">
            <div class="section-heading">
                <h2>Everything you need for online exams</h2>
                <p>Essential tools for students and examination administrators.</p>
            </div>

            <div class="feature-grid">
                <article class="feature-card">
                    <div class="feature-icon" aria-hidden="true">01</div>
                    <h3>Easy Exam Access</h3>
                    <p>
                        View available examinations, read instructions,
                        and begin an exam when you are ready.
                    </p>
                </article>

                <article class="feature-card">
                    <div class="feature-icon" aria-hidden="true">02</div>
                    <h3>Timed Examinations</h3>
                    <p>
                        Complete exams within their allotted time with a
                        clear countdown and a structured question interface.
                    </p>
                </article>

                <article class="feature-card">
                    <div class="feature-icon" aria-hidden="true">03</div>
                    <h3>Results and Performance</h3>
                    <p>
                        Review your submitted examinations and see your
                        scores when results are available.
                    </p>
                </article>
            </div>
        </div>
    </section>

    <section class="how-it-works" id="how-it-works">
        <div class="container">
            <div class="section-heading">
                <h2>How it works</h2>
                <p>Get started in three simple steps.</p>
            </div>

            <div class="steps">
                <article class="step">
                    <div class="step-number">1</div>
                    <h3>Create an Account</h3>
                    <p>Register as a student to access the examination portal.</p>
                </article>

                <article class="step">
                    <div class="step-number">2</div>
                    <h3>Take Your Exam</h3>
                    <p>Choose an available examination and submit your answers.</p>
                </article>

                <article class="step">
                    <div class="step-number">3</div>
                    <h3>View Your Results</h3>
                    <p>Access your scores and submitted examination results.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="cta">
        <div class="container">
            <h2>Ready to get started?</h2>
            <p>Create your account and access your examination dashboard.</p>
            <a class="button" href="register.php">Register Now</a>
        </div>
    </section>
</main>

<footer>
    <div class="container footer-content">
        <span>&copy; <?= date('Y') ?> ExamPortal. All rights reserved.</span>
        <span>Online Examination Portal</span>
    </div>
</footer>

</body>
</html>
```