<?php
if (basename(isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '') === basename(__FILE__)) {
    header('HTTP/1.1 403 Forbidden');
    exit('Forbidden');
}

return array(
    'username' => 'admin',
    'password_sha256' => 'CHANGE_ME',
    'db_host' => 'sdb-82.hosting.stackcp.net',
    'db_name' => 'yellowarrow-35303839de1b',
    'db_user' => 'YOUR_DATABASE_USERNAME',
    'db_pass' => 'YOUR_DATABASE_PASSWORD',
    'db_charset' => 'utf8mb4',
    'project_upload_dir' => dirname(__DIR__) . '/assets/projects',
    'project_upload_url' => 'assets/projects'
);
