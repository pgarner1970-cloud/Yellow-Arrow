<?php
require dirname(__DIR__) . '/admin/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    // Public API reads existing MySQL portfolio data only.
    // It intentionally does not create/alter tables, because some hosting accounts
    // allow SELECT but restrict CREATE/ALTER on public requests.
    echo json_encode(ya_get_projects_from_database($config, true));
} catch (Exception $e) {
    http_response_code(500);
    $payload = array('error' => 'Unable to load portfolio projects.');

    // Temporary diagnostics: visit /api/portfolio-projects.php?debug=1 while logged in
    // or during setup to confirm whether this is a database login/table/query issue.
    // This does not print database passwords.
    if (isset($_GET['debug']) && $_GET['debug'] === '1') {
        $payload['debug'] = array(
            'message' => $e->getMessage(),
            'php' => PHP_VERSION
        );
    }

    echo json_encode($payload);
}
