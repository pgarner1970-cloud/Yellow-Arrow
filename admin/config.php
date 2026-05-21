<?php
if (basename(isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '') === basename(__FILE__)) {
    header('HTTP/1.1 403 Forbidden');
    exit('Forbidden');
}

return array(
    'username' => 'admin',

    // Admin password set in this patch: Quantum99gpu!
    // To create a new password hash on Linux safely:
    // php -r 'echo hash("sha256", "YOUR_NEW_PASSWORD");'
    'password_sha256' => '2074585b6606fa2fbc82b00f65eb58e29efd32003d61c93f161b668e65777753',

    // MySQL settings - update user/password before uploading.
    'db_host' => 'sdb-82.hosting.stackcp.net',
    'db_name' => 'yellowarrow-35303839de1b',
    'db_user' => 'yellowarrow-35303839de1b',
    'db_pass' => 'Quantum99gpu!',
    'db_charset' => 'utf8mb4',

    'project_upload_dir' => dirname(__DIR__) . '/assets/projects',
    'project_upload_url' => 'assets/projects'
);
