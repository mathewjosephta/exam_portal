<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        set_flash('error', 'Please log in to continue.');
        redirect('/exam_portal/login.php');
    }
}

function require_student(): void
{
    require_login();

    if (($_SESSION['role'] ?? '') !== 'student') {
        http_response_code(403);
        exit('Access denied. This page is for students only.');
    }

    if (($_SESSION['status'] ?? '') !== 'active') {
        session_destroy();
        redirect('/exam_portal/login.php');
    }
}

function require_admin(): void
{
    require_login();

    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Access denied. Administrator permissions are required.');
    }

    if (($_SESSION['status'] ?? '') !== 'active') {
        session_destroy();
        redirect('/exam_portal/login.php');
    }
}