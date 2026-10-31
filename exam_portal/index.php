<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Home';
$basePath = '/exam_portal';

require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">

    <div class="hero-badge">
        <span class="badge-dot"></span>
        Your exams, simplified
    </div>

    <h1>
        Less stress.<br>
        Better <span class="highlight">scores.</span>
    </h1>

    <p class="hero-description">
        Your exams, your pace, your progress.
        Take online examinations in one simple,
        organized space.
    </p>

    <div class="hero-actions">

        <?php if (!empty($_SESSION['user_id'])): ?>

            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>

                <a href="<?= e($basePath) ?>/admin/dashboard.php"
                   class="btn btn-primary">
                    Open dashboard <span aria-hidden="true">↗</span>
                </a>

            <?php else: ?>

                <a href="<?= e($basePath) ?>/student/dashboard.php"
                   class="btn btn-primary">
                    Explore exams <span aria-hidden="true">↗</span>
                </a>

            <?php endif; ?>

        <?php else: ?>

            <a href="<?= e($basePath) ?>/register.php"
               class="btn btn-primary">
                Get started <span aria-hidden="true">↗</span>
            </a>

            <a href="<?= e($basePath) ?>/login.php"
               class="btn btn-outline">
                I have an account
            </a>

        <?php endif; ?>

    </div>

    <div class="hero-art" aria-hidden="true">

        <div class="hero-art-bg"></div>

        <div class="art-card art-card-one">
            <strong>Today's progress</strong>

            <span>Keep moving forward.</span>

            <div class="art-progress">
                <span></span>
            </div>
        </div>

        <div class="art-card art-card-two">
            <strong>Ready when you are.</strong>

            <span>Your next exam starts here.</span>
        </div>

    </div>

</section>


<section class="section">

    <div class="container">

        <div class="section-heading text-center">

            <div class="hero-badge">
                Built for students
            </div>

            <h2>
                Everything you need.<br>
                Nothing you don't.
            </h2>

            <p>
                A simple place to take exams, track your results,
                and manage your academic progress.
            </p>

        </div>


        <div class="card-grid">

            <article class="card feature-card">

                <div class="feature-icon" aria-hidden="true">
                    ↗
                </div>

                <h3>Exams made simple</h3>

                <p>
                    Find available examinations, read the instructions,
                    and answer questions in a clean interface.
                </p>

            </article>


            <article class="card feature-card">

                <div class="feature-icon" aria-hidden="true">
                    ✓
                </div>

                <h3>Instant evaluation</h3>

                <p>
                    Get your results after submission when the
                    examination's result settings allow it.
                </p>

            </article>


            <article class="card feature-card">

                <div class="feature-icon" aria-hidden="true">
                    %
                </div>

                <h3>Track your progress</h3>

                <p>
                    Review previous attempts and see how your
                    performance changes over time.
                </p>

            </article>

        </div>

    </div>

</section>


<section class="section">

    <div class="container">

        <div
            class="card"
            style="
                background: var(--primary);
                padding: 55px 35px;
                border: none;
                text-align: center;
                border-radius: 30px;
            "
        >

            <h2 style="margin-bottom: 18px;">
                Your next exam.<br>
                Your next step.
            </h2>

            <p
                style="
                    max-width: 500px;
                    margin: 0 auto 25px;
                    color: #244b35;
                "
            >
                Log in to see your available examinations
                and keep your learning on track.
            </p>

            <?php if (empty($_SESSION['user_id'])): ?>

                <a
                    href="<?= e($basePath) ?>/register.php"
                    class="btn btn-dark"
                >
                    Create your account
                    <span aria-hidden="true">↗</span>
                </a>

            <?php else: ?>

                <a
                    href="<?= e($basePath) ?>/<?= ($_SESSION['role'] ?? '') === 'admin' ? 'admin' : 'student' ?>/dashboard.php"
                    class="btn btn-dark"
                >
                    Go to dashboard
                    <span aria-hidden="true">↗</span>
                </a>

            <?php endif; ?>

        </div>

    </div>

</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>