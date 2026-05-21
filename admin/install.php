<?php
require dirname(__FILE__) . '/functions.php';
ya_require_login();

$message = '';
$error = '';

try {
    ya_db_install($config);
    $message = 'Database tables are ready.';

    // Optional one-time import from old JSON if database is empty.
    $projects = ya_get_projects($config, false);
    $jsonFile = dirname(__DIR__) . '/assets/data/projects.json';

    if (!count($projects) && file_exists($jsonFile)) {
        $json = file_get_contents($jsonFile);
        $data = json_decode($json, true);

        if (is_array($data)) {
            foreach ($data as $item) {
                if (is_array($item)) {
                    $project = ya_normalise_project($item);
                    ya_save_project($config, null, $project);
                }
            }
            $message .= ' Existing projects.json imported into MySQL.';
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

ya_render_header('Install Portfolio Database');
?>
<header class="admin-topbar">
  <div>
    <h1>Portfolio Database Setup</h1>
    <p>Create or verify the MySQL tables for the portfolio system.</p>
  </div>
  <nav>
    <a href="index.php">Back to Projects</a>
    <a href="logout.php">Logout</a>
  </nav>
</header>

<main class="admin-container">
  <?php if ($error !== ''): ?>
    <div class="admin-alert"><?php echo ya_h($error); ?></div>
  <?php else: ?>
    <div class="admin-help"><?php echo ya_h($message); ?></div>
  <?php endif; ?>
</main>
<?php ya_render_footer(); ?>
