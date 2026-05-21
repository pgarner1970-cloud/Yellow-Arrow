<?php
if (basename(isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '') === basename(__FILE__)) {
    header('HTTP/1.1 403 Forbidden');
    exit('Forbidden');
}

if (!isset($_SESSION)) {
    session_start();
}

$config = require dirname(__FILE__) . '/config.php';

function ya_is_logged_in() {
    return !empty($_SESSION['ya_portfolio_admin']);
}

function ya_require_login() {
    if (!ya_is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function ya_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function ya_get($array, $key, $default) {
    if (is_array($array) && array_key_exists($key, $array)) {
        return $array[$key];
    }
    return $default;
}

function ya_slugify($text) {
    $text = strtolower(trim((string)$text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');

    if ($text !== '') {
        return $text;
    }

    return 'project';
}

function ya_csv_to_array($value) {
    $parts = explode(',', (string)$value);
    $out = array();

    foreach ($parts as $part) {
        $part = trim($part);
        if ($part !== '') {
            $out[] = $part;
        }
    }

    return $out;
}

function ya_array_to_csv($array) {
    if (!is_array($array)) {
        return '';
    }
    return implode(', ', $array);
}

function ya_json_encode($value) {
    $json = json_encode($value);
    if ($json === false) {
        return '[]';
    }
    return $json;
}

function ya_json_decode_array($value) {
    $data = json_decode((string)$value, true);
    if (is_array($data)) {
        return $data;
    }
    return array();
}

function ya_db($config) {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    if (!class_exists('PDO')) {
        throw new RuntimeException('PDO is not available on this PHP installation.');
    }

    $charset = isset($config['db_charset']) ? $config['db_charset'] : 'utf8mb4';
    $dsn = 'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=' . $charset;

    $options = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    );

    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
    return $pdo;
}

function ya_db_install($config) {
    $db = ya_db($config);

    $db->exec("
        CREATE TABLE IF NOT EXISTS portfolio_projects (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            slug VARCHAR(160) NOT NULL,
            name VARCHAR(255) NOT NULL,
            url VARCHAR(255) NOT NULL,
            location VARCHAR(255) DEFAULT '',
            description TEXT,
            services TEXT,
            visible TINYINT(1) NOT NULL DEFAULT 1,
            responsive TINYINT(1) NOT NULL DEFAULT 1,
            desktop_mode ENUM('auto','manual') NOT NULL DEFAULT 'auto',
            desktop_thumbnail VARCHAR(255) DEFAULT '',
            notes TEXT,
            sort_order INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug_unique (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS portfolio_screenshots (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            project_id INT UNSIGNED NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            caption VARCHAR(255) DEFAULT '',
            sort_order INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY project_id_idx (project_id),
            CONSTRAINT portfolio_screenshots_project_fk
                FOREIGN KEY (project_id)
                REFERENCES portfolio_projects(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}

function ya_project_from_row($row, $screenshots) {
    return array(
        'id' => ya_get($row, 'slug', ''),
        'dbId' => (int)ya_get($row, 'id', 0),
        'name' => ya_get($row, 'name', ''),
        'url' => ya_get($row, 'url', ''),
        'location' => ya_get($row, 'location', ''),
        'description' => ya_get($row, 'description', ''),
        'services' => ya_json_decode_array(ya_get($row, 'services', '[]')),
        'visible' => (int)ya_get($row, 'visible', 1) === 1,
        'responsive' => (int)ya_get($row, 'responsive', 1) === 1,
        'desktopMode' => ya_get($row, 'desktop_mode', 'auto'),
        'desktopThumbnail' => ya_get($row, 'desktop_thumbnail', ''),
        'adminScreenshots' => $screenshots,
        'notes' => ya_get($row, 'notes', '')
    );
}



function ya_get_projects_from_database($config, $public_only) {
    $db = ya_db($config);

    if ($public_only) {
        $stmt = $db->query("SELECT * FROM portfolio_projects WHERE visible = 1 ORDER BY sort_order ASC, id ASC");
    } else {
        $stmt = $db->query("SELECT * FROM portfolio_projects ORDER BY sort_order ASC, id ASC");
    }

    $projects = array();

    while ($row = $stmt->fetch()) {
        $shots = ya_get_project_screenshots($config, (int)$row['id']);
        $project = ya_project_from_row($row, $shots);

        if ($public_only) {
            unset($project['notes']);
            unset($project['dbId']);
        }

        $projects[] = $project;
    }

    return $projects;
}

function ya_get_project_screenshots($config, $project_id) {
    $db = ya_db($config);
    $stmt = $db->prepare("SELECT image_path, caption FROM portfolio_screenshots WHERE project_id = ? ORDER BY sort_order ASC, id ASC");
    $stmt->execute(array($project_id));

    $shots = array();
    while ($row = $stmt->fetch()) {
        $path = trim((string)ya_get($row, 'image_path', ''));
        if ($path !== '') {
            $shots[] = array(
                'image' => $path,
                'caption' => trim((string)ya_get($row, 'caption', ''))
            );
        }
    }

    return $shots;
}

function ya_get_projects($config, $public_only) {
    ya_db_install($config);
    return ya_get_projects_from_database($config, $public_only);
}

function ya_get_project($config, $id) {
    ya_db_install($config);
    $db = ya_db($config);
    $stmt = $db->prepare("SELECT * FROM portfolio_projects WHERE id = ?");
    $stmt->execute(array((int)$id));
    $row = $stmt->fetch();

    if (!$row) {
        return null;
    }

    $shots = ya_get_project_screenshots($config, (int)$row['id']);
    return ya_project_from_row($row, $shots);
}

function ya_normalise_project($input) {
    $name = trim((string)ya_get($input, 'name', ''));
    $slug = trim((string)ya_get($input, 'id', ''));

    if ($slug === '') {
        if ($name !== '') {
            $slug = ya_slugify($name);
        } else {
            $slug = 'project';
        }
    }

    $services = ya_get($input, 'services', '');
    if (!is_array($services)) {
        $services = ya_csv_to_array($services);
    }

    $adminScreenshots = ya_get($input, 'adminScreenshots', array());
    if (!is_array($adminScreenshots)) {
        $adminScreenshots = array();
    }

    $normalisedShots = array();

    foreach ($adminScreenshots as $shot) {
        if (is_array($shot)) {
            $normalisedShots[] = array(
                'image' => trim((string)ya_get($shot, 'image', '')),
                'caption' => trim((string)ya_get($shot, 'caption', ''))
            );
        } else {
            $shot = trim((string)$shot);
            if ($shot !== '') {
                $normalisedShots[] = array(
                    'image' => $shot,
                    'caption' => ''
                );
            }
        }
    }

    $adminScreenshots = $normalisedShots;

    $desktopMode = ya_get($input, 'desktopMode', 'auto');
    if ($desktopMode !== 'auto' && $desktopMode !== 'manual') {
        $desktopMode = 'auto';
    }

    return array(
        'id' => ya_slugify($slug),
        'name' => $name,
        'url' => trim((string)ya_get($input, 'url', '')),
        'location' => trim((string)ya_get($input, 'location', '')),
        'description' => trim((string)ya_get($input, 'description', '')),
        'services' => array_values($services),
        'visible' => !empty($input['visible']),
        'responsive' => !empty($input['responsive']),
        'desktopMode' => $desktopMode,
        'desktopThumbnail' => trim((string)ya_get($input, 'desktopThumbnail', '')),
        'adminScreenshots' => array_values($adminScreenshots),
        'notes' => trim((string)ya_get($input, 'notes', ''))
    );
}

function ya_save_project($config, $db_id, $project) {
    ya_db_install($config);
    $db = ya_db($config);

    $params = array(
        $project['id'],
        $project['name'],
        $project['url'],
        $project['location'],
        $project['description'],
        ya_json_encode($project['services']),
        !empty($project['visible']) ? 1 : 0,
        !empty($project['responsive']) ? 1 : 0,
        $project['desktopMode'],
        $project['desktopThumbnail'],
        $project['notes']
    );

    if ($db_id) {
        $params[] = (int)$db_id;
        $stmt = $db->prepare("
            UPDATE portfolio_projects
            SET slug = ?, name = ?, url = ?, location = ?, description = ?, services = ?,
                visible = ?, responsive = ?, desktop_mode = ?, desktop_thumbnail = ?, notes = ?
            WHERE id = ?
        ");
        $stmt->execute($params);
        $project_id = (int)$db_id;
    } else {
        $stmt = $db->prepare("
            INSERT INTO portfolio_projects
            (slug, name, url, location, description, services, visible, responsive, desktop_mode, desktop_thumbnail, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute($params);
        $project_id = (int)$db->lastInsertId();
    }

    $db->prepare("DELETE FROM portfolio_screenshots WHERE project_id = ?")->execute(array($project_id));

    $order = 0;
    foreach ($project['adminScreenshots'] as $shot) {
        if (is_array($shot)) {
            $shotPath = trim((string)ya_get($shot, 'image', ''));
            $shotCaption = trim((string)ya_get($shot, 'caption', ''));
        } else {
            $shotPath = trim((string)$shot);
            $shotCaption = '';
        }

        if ($shotPath !== '') {
            $stmt = $db->prepare("INSERT INTO portfolio_screenshots (project_id, image_path, caption, sort_order) VALUES (?, ?, ?, ?)");
            $stmt->execute(array($project_id, $shotPath, $shotCaption, $order));
            $order++;
        }
    }

    return $project_id;
}

function ya_delete_project($config, $id) {
    ya_db_install($config);
    $db = ya_db($config);
    $stmt = $db->prepare("DELETE FROM portfolio_projects WHERE id = ?");
    $stmt->execute(array((int)$id));
}

function ya_upload_project_file($config, $field, $project_slug) {
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        return null;
    }

    if (!isset($_FILES[$field]['error']) || $_FILES[$field]['error'] == UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES[$field]['error'] != UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed for ' . $field . '.');
    }

    $allowed = array(
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    );

    $tmp = $_FILES[$field]['tmp_name'];
    $mime = '';

    if (function_exists('mime_content_type')) {
        $mime = mime_content_type($tmp);
    }

    if (!isset($allowed[$mime])) {
        $info = getimagesize($tmp);
        if (is_array($info) && isset($info['mime'])) {
            $mime = $info['mime'];
        }
    }

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG and WebP images are allowed.');
    }

    if ($_FILES[$field]['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Image uploads must be under 5MB.');
    }

    $dir = rtrim($config['project_upload_dir'], '/');
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            throw new RuntimeException('Unable to create upload directory.');
        }
    }

    $safe_id = ya_slugify($project_slug);
    $name = $safe_id . '-' . ya_slugify($field) . '-' . date('Ymd-His') . '.' . $allowed[$mime];
    $target = $dir . '/' . $name;

    if (!move_uploaded_file($tmp, $target)) {
        throw new RuntimeException('Unable to move uploaded image.');
    }

    return rtrim($config['project_upload_url'], '/') . '/' . $name;
}

function ya_render_header($title) {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<meta name="robots" content="noindex,nofollow">';
    echo '<title>' . ya_h($title) . ' | Yellow Arrow</title>';
    echo '<link rel="stylesheet" href="../assets/css/styles.css">';
    echo '<link rel="stylesheet" href="admin.css">';
    echo '<link rel="icon" href="../assets/images/favicon.svg" type="image/svg+xml">';
    echo '<link rel="shortcut icon" href="../favicon.svg" type="image/svg+xml">';
    echo '</head><body class="admin-body">';
}

function ya_render_footer() {
    echo '</body></html>';
}
