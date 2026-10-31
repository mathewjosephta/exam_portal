<?php
$pageTitle = $pageTitle ?? 'Exam Portal';
$basePath = $basePath ?? app_url('');
$isLoggedIn = !empty($_SESSION['user_id']);
$minimalNavigation = $minimalNavigation ?? false;
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
    href="<?= e($basePath) ?>/assets/css/style.css?v=6"
>

    <script
        src="<?= e($basePath) ?>/assets/js/main.js?v=2"
        defer
    ></script>
</head>

<body class="<?= $isLoggedIn ? 'authenticated-page' : '' ?>">

<?php if (!$isLoggedIn): ?>
    <header class="site-header">
        <div class="nav-container">
            <?php if ($minimalNavigation): ?>
                <a href="<?= e($basePath) ?>/index.php" class="back-navigation">
                    <span aria-hidden="true">←</span> Back
                </a>
            <?php else: ?>
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

                <nav class="main-nav" id="mainNav" aria-label="Main navigation">
                    <a href="<?= e($basePath) ?>/login.php">Log in</a>
                    <a href="<?= e($basePath) ?>/register.php" class="btn btn-primary btn-small">
                        Get started <span aria-hidden="true">↗</span>
                    </a>
                </nav>
            <?php endif; ?>
        </div>
    </header>
<?php endif; ?>

<main>
    <?php render_flash_messages(); ?>