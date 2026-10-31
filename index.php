<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Home';
$basePath = app_url('');

require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="hero-copy">
        <div class="hero-badge">
            <span class="badge-dot"></span>
            Your exams, simplified
        </div>

        <h1>
            Make every<br>
            answer <span class="highlight">count.</span>
        </h1>

        <p class="hero-description">
            A calmer way to take exams, follow your progress, and focus on what
            comes next. Everything you need, all in one place.
        </p>

        <div class="hero-actions">
            <?php if (!empty($_SESSION['user_id'])): ?>
                <a
                    href="<?= e($basePath) ?>/<?= ($_SESSION['role'] ?? '') === 'admin' ? 'admin' : 'student' ?>/dashboard.php"
                    class="btn btn-primary"
                >
                    Open your dashboard <span aria-hidden="true">↗</span>
                </a>
            <?php else: ?>
                <a href="<?= e($basePath) ?>/register.php" class="btn btn-primary">
                    Get started <span aria-hidden="true">↗</span>
                </a>
                <a href="<?= e($basePath) ?>/login.php" class="btn btn-outline">
                    I have an account
                </a>
            <?php endif; ?>
        </div>

        <div class="hero-trust">
            <span class="hero-trust-mark" aria-hidden="true">✓</span>
            <span>Simple, focused, and ready when you are</span>
        </div>
    </div>

    <div class="hero-art" aria-label="Illustration of a student studying online">
        <div class="hero-art-bg"></div>
        <svg class="hero-illustration" viewBox="0 0 560 440" role="img" aria-labelledby="study-title study-description">
            <title id="study-title">A focused study session</title>
            <desc id="study-description">A student studying at a desk with a laptop, books, and a plant.</desc>
            <defs>
                <linearGradient id="desk" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stop-color="#b6f3cc"/>
                    <stop offset="1" stop-color="#65d994"/>
                </linearGradient>
                <linearGradient id="screen" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="#263b32"/>
                    <stop offset="1" stop-color="#14271f"/>
                </linearGradient>
            </defs>
            <circle cx="286" cy="213" r="166" fill="#d5f8df"/>
            <circle cx="286" cy="213" r="134" fill="#eaffef"/>
            <path d="M92 344h383" stroke="#9cdcb0" stroke-width="8" stroke-linecap="round"/>
            <path d="M127 347v58m302-58v58" stroke="#75c88e" stroke-width="10" stroke-linecap="round"/>

            <g class="illustration-plant">
                <path d="M459 333V257" stroke="#438b58" stroke-width="6" stroke-linecap="round"/>
                <path d="M458 281c-28-2-36-20-32-37 18 2 32 13 32 37Zm2-21c2-25 16-37 34-36 1 19-9 33-34 36Zm-2 42c-21-2-29-15-27-29 16 1 27 11 27 29Z" fill="#65c77e"/>
                <path d="M437 331h44l-6 28h-32l-6-28Z" fill="#f3a978"/>
            </g>

            <g class="illustration-person">
                <path d="M201 259c5-45 33-69 74-69 44 0 68 28 72 69l7 78H194l7-78Z" fill="#6d67cf"/>
                <path d="M219 258c10 9 23 14 40 14 19 0 36-8 47-21l15 85h-111l9-78Z" fill="#8982ed"/>
                <path d="M240 209c-5 25 6 46 28 46 25 0 36-22 30-47-5-21-15-31-30-31-16 0-24 11-28 32Z" fill="#b97553"/>
                <path d="M236 207c-4-29 12-51 39-50 24 1 37 18 31 43-7-10-17-15-30-16-12 11-23 17-40 19Z" fill="#263b32"/>
                <path d="M258 222c5 4 11 4 16 0" fill="none" stroke="#70402f" stroke-width="3" stroke-linecap="round"/>
                <path d="M258 205h2m19 0h2" stroke="#263b32" stroke-width="4" stroke-linecap="round"/>
            </g>

            <g class="illustration-laptop">
                <path d="M154 278h202l21 71H131l23-71Z" fill="url(#screen)"/>
                <rect x="166" y="289" width="177" height="48" rx="5" fill="#f7fff9"/>
                <rect x="180" y="300" width="64" height="6" rx="3" fill="#b9e8c8"/>
                <rect x="180" y="313" width="123" height="5" rx="2.5" fill="#dfeee3"/>
                <rect x="180" y="324" width="103" height="5" rx="2.5" fill="#dfeee3"/>
                <circle cx="321" cy="311" r="13" fill="#25d878"/>
                <path d="m315 311 4 4 8-9" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M131 349h246l-13 12H145l-14-12Z" fill="#52665a"/>
            </g>

            <g class="illustration-book">
                <path d="M95 320h39v26H95c-7 0-12-5-12-12s5-14 12-14Z" fill="#f3a978"/>
                <path d="M100 302h36v22h-36c-7 0-11-4-11-11s4-11 11-11Z" fill="#f6ce73"/>
                <path d="M100 302v22m-5-4h39m-39 20h39" stroke="#fff7df" stroke-width="3"/>
            </g>

            <g class="illustration-sparkles" fill="#f3b84e">
                <path d="m391 107 5 13 13 5-13 5-5 13-5-13-13-5 13-5 5-13Z"/>
                <path d="m147 156 3 8 8 3-8 3-3 8-3-8-8-3 8-3 3-8Z"/>
            </g>
            <circle cx="420" cy="196" r="6" fill="#f3a978"/>
            <circle cx="145" cy="247" r="5" fill="#25d878"/>
        </svg>

        <div class="art-card art-card-one">
            <span class="art-card-icon" aria-hidden="true">✓</span>
            <div>
                <strong>One question at a time</strong>
                <span>Stay focused. Do your best.</span>
            </div>
        </div>

        <div class="art-card art-card-two">
            <span class="art-progress-ring" aria-hidden="true">↗</span>
            <div>
                <strong>Progress, made clear</strong>
                <span>Your results, all in one place.</span>
            </div>
        </div>
    </div>
</section>

<section class="section landing-features">
    <div class="container">
        <div class="section-heading text-center reveal-on-scroll">
            <div class="hero-badge">Built for students</div>
            <h2>Everything you need.<br>Nothing you don't.</h2>
            <p>A simple place to take exams, track your results, and manage your academic progress.</p>
        </div>

        <div class="card-grid">
            <article class="card feature-card reveal-on-scroll">
                <div class="feature-icon" aria-hidden="true">↗</div>
                <h3>Exams made simple</h3>
                <p>Find available examinations, read the instructions, and answer questions in a clean interface.</p>
            </article>
            <article class="card feature-card reveal-on-scroll">
                <div class="feature-icon" aria-hidden="true">✓</div>
                <h3>Instant evaluation</h3>
                <p>Get your results after submission when the examination's result settings allow it.</p>
            </article>
            <article class="card feature-card reveal-on-scroll">
                <div class="feature-icon" aria-hidden="true">%</div>
                <h3>Track your progress</h3>
                <p>Review previous attempts and see how your performance changes over time.</p>
            </article>
        </div>
    </div>
</section>

<section class="section landing-cta-section">
    <div class="container">
        <div class="landing-cta reveal-on-scroll">
            <div class="landing-cta-copy">
                <span class="cta-kicker">Your next step starts here</span>
                <h2>Ready when<br>you are.</h2>
                <p>Log in to find your exams and keep your learning on track.</p>
                <?php if (empty($_SESSION['user_id'])): ?>
                    <a href="<?= e($basePath) ?>/register.php" class="btn btn-dark">
                        Create your account <span aria-hidden="true">↗</span>
                    </a>
                <?php else: ?>
                    <a
                        href="<?= e($basePath) ?>/<?= ($_SESSION['role'] ?? '') === 'admin' ? 'admin' : 'student' ?>/dashboard.php"
                        class="btn btn-dark"
                    >
                        Go to dashboard <span aria-hidden="true">↗</span>
                    </a>
                <?php endif; ?>
            </div>
            <div class="cta-decoration" aria-hidden="true">
                <span class="cta-orbit cta-orbit-one"></span>
                <span class="cta-orbit cta-orbit-two"></span>
                <span class="cta-spark">✦</span>
                <span class="cta-check">✓</span>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
