<?php

declare(strict_types=1);

function render_admin_sidebar(): string
{
    $links = [
        ['Dashboard', 'admin/dashboard.php'],
        ['Students', 'admin/students.php'],
        ['Exams', 'admin/exams.php'],
        ['Questions', 'admin/questions.php'],
        ['Results', 'admin/results.php'],
    ];
    $html = '<aside class="sidebar"><div class="brand-box"><strong>Exam Portal</strong><small>Admin</small></div><nav class="side-links">';
    foreach ($links as [$label, $url]) {
        $active = strpos($_SERVER['SCRIPT_NAME'] ?? '', $url) !== false ? ' active' : '';
        $html .= '<a class="side-link' . $active . '" href="' . e(app_url($url)) . '">' . e($label) . '</a>';
    }
    $html .= '<a class="side-link danger" href="' . e(app_url('logout.php')) . '">Logout</a>';
    $html .= '</nav></aside>';
    return $html;
}
