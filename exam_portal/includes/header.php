<?php
$pageTitle = $pageTitle ?? 'Exam Portal';
$basePath = $basePath ?? '/exam_portal';
$isLoggedIn = !empty($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($pageTitle) ?> | Exam Portal</title>

    <meta
        name="description"
        content="A simple and modern online examination portal."
    >

    <link
        rel="stylesheet"
        href="<?= e($basePath) ?>/assets/css/style.css"
    >

    <script
        src="<?= e($basePath) ?>/assets/js/main.js"
        defer
    ></script>
</head>

<body>

<header class="site-header">
    <div class="nav-container">

        <a href="<?= e($basePath) ?>/index.php" class="brand">
            <span class="brand-mark">e.</span>
            <span>exam<span class="brand-light">space</span></span>
        </a>

        <button
            class="menu-toggle"
            id="menuToggle"
            aria-label="Toggle navigation"
            aria-expanded="false"
            aria-controls="mainNav"
            type="button"
        >
            <span></span>
            <span></span>
        </button>

        <nav class="main-nav" id="mainNav">

            <a href="<?= e($basePath) ?>/index.php">
                Home
            </a>

            <?php if ($isLoggedIn): ?>

                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>

                    <a href="<?= e($basePath) ?>/admin/dashboard.php">
                        Dashboard
                    </a>

                <?php else: ?>

                    <a href="<?= e($basePath) ?>/student/dashboard.php">
                        Dashboard
                    </a>

                <?php endif; ?>

                <form
                    action="<?= e($basePath) ?>/logout.php"
                    method="POST"
                    class="nav-logout-form"
                >
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e(csrf_token()) ?>"
                    >

                    <button class="btn btn-dark btn-small" type="submit">
                        Logout
                    </button>
                </form>

            <?php else: ?>

                <a href="<?= e($basePath) ?>/login.php">
                    Log in
                </a>

                <a
                    href="<?= e($basePath) ?>/register.php"
                    class="btn btn-primary btn-small"
                >
                    Get started <span aria-hidden="true">↗</span>
                </a>

            <?php endif; ?>

        </nav>

    </div>
</header>

<main>