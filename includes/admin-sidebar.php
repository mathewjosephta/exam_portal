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
    $current = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $html = '<aside class="sidebar">'
        . '<a class="brand sidebar-brand" href="' . e(app_url('admin/dashboard.php')) . '">'
        . '<span class="brand-mark">e.</span><span>exam<span class="brand-light">space</span></span></a>'
        . '<p class="sidebar-label">Administration</p><nav class="sidebar-nav" aria-label="Admin navigation">';

    foreach ($links as [$label, $url]) {
        $active = str_ends_with($current, '/' . basename($url)) ? ' active' : '';
        $html .= '<a class="side-link' . $active . '" href="' . e(app_url($url)) . '">'
            . e($label) . '</a>';
    }

    $html .= '</nav><form class="sidebar-logout" method="post" action="'
        . e(app_url('logout.php')) . '"><input type="hidden" name="csrf_token" value="'
        . e(csrf_token()) . '"><button class="side-link logout-button" type="submit">'
        . '<span aria-hidden="true">↪</span> Log out</button></form></aside>';

    return $html;
}
