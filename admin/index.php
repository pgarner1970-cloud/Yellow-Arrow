<?php
require dirname(__FILE__) . '/functions.php';
ya_require_login();

$error = '';
$projects = array();

try {
    $projects = ya_get_projects($config, false);
} catch (Exception $e) {
    $error = $e->getMessage();
}

ya_render_header('Portfolio Projects');
?>
<header class="admin-topbar">
  <div>
    <h1>Portfolio Projects</h1>
    <p>Manage the projects shown on the Web Design portfolio section.</p>
  </div>
  <nav>
    <a href="install.php">Install / Check DB</a>
    <a href="../web-design.html" target="_blank" rel="noopener">View Web Design Page</a>
    <a href="edit.php">Add Project</a>
    <a href="logout.php">Logout</a>
  </nav>
</header>

<main class="admin-container">
  <?php if ($error !== ''): ?>
    <div class="admin-alert"><?php echo ya_h($error); ?></div>
  <?php else: ?>
    <div class="admin-help">
      <strong>Screenshot guidance:</strong>
      use automatic desktop thumbnails for quick setup, but upload a manual desktop screenshot for best results.
    </div>

    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Project</th>
            <th>URL</th>
            <th>Visible</th>
            <th>Screenshots</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($projects as $project): ?>
            <tr>
              <td>
                <strong><?php echo ya_h(ya_get($project, 'name', 'Untitled')); ?></strong>
                <span><?php echo ya_h(ya_get($project, 'location', '')); ?></span>
              </td>
              <td><?php echo ya_h(ya_get($project, 'url', '')); ?></td>
              <td><?php echo !empty($project['visible']) ? 'Yes' : 'No'; ?></td>
              <td>
                <?php
                  $shots = array();
                  if (ya_get($project, 'desktopThumbnail', '') !== '') { $shots[] = 'Desktop'; }
                  $adminShots = ya_get($project, 'adminScreenshots', array());
                  if (is_array($adminShots) && count($adminShots) > 0) { $shots[] = 'Admin'; }
                  echo ya_h(count($shots) ? implode(', ', $shots) : 'Auto/none');
                ?>
              </td>
              <td><a class="admin-small-button" href="edit.php?id=<?php echo (int)ya_get($project, 'dbId', 0); ?>">Edit</a></td>
            </tr>
          <?php endforeach; ?>

          <?php if (!count($projects)): ?>
            <tr><td colspan="5">No projects yet. Use “Install / Check DB” to import from the old JSON if available.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</main>
<?php ya_render_footer(); ?>
