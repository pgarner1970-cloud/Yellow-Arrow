<?php
require dirname(__FILE__) . '/functions.php';
ya_require_login();

$db_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = $db_id > 0;
$error = '';

if ($is_edit) {
    $project = ya_get_project($config, $db_id);
    if (!$project) {
        header('Location: index.php');
        exit;
    }
} else {
    $project = array(
        'id' => '',
        'name' => '',
        'url' => '',
        'location' => '',
        'description' => '',
        'services' => array(),
        'visible' => true,
        'responsive' => true,
        'desktopMode' => 'auto',
        'desktopThumbnail' => '',
        'adminScreenshots' => array(),
        'notes' => ''
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['delete']) && $is_edit) {
            ya_delete_project($config, $db_id);
            header('Location: index.php');
            exit;
        }

        $project = ya_normalise_project($_POST);

        $keptAdminScreenshots = array();
        if (isset($_POST['keepAdminScreenshots']) && is_array($_POST['keepAdminScreenshots'])) {
            foreach ($_POST['keepAdminScreenshots'] as $keptShot) {
                if (is_array($keptShot)) {
                    $shotImage = trim((string)ya_get($keptShot, 'image', ''));
                    $shotCaption = trim((string)ya_get($keptShot, 'caption', ''));
                    if ($shotImage !== '') {
                        $keptAdminScreenshots[] = array(
                            'image' => $shotImage,
                            'caption' => $shotCaption
                        );
                    }
                } else {
                    $keptShot = trim((string)$keptShot);
                    if ($keptShot !== '') {
                        $keptAdminScreenshots[] = array(
                            'image' => $keptShot,
                            'caption' => ''
                        );
                    }
                }
            }
        }

        $project['adminScreenshots'] = $keptAdminScreenshots;

        $uploaded = ya_upload_project_file($config, 'desktop', $project['id']);
        if ($uploaded !== null) {
            $project['desktopThumbnail'] = $uploaded;
            $project['desktopMode'] = 'manual';
        }

        $adminUploaded = ya_upload_project_file($config, 'adminScreenshot', $project['id']);
        if ($adminUploaded !== null) {
            $project['adminScreenshots'][] = array(
                'image' => $adminUploaded,
                'caption' => trim((string)ya_get($_POST, 'newAdminScreenshotCaption', ''))
            );
        }

        ya_save_project($config, $is_edit ? $db_id : null, $project);
        header('Location: index.php');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

ya_render_header($is_edit ? 'Edit Project' : 'Add Project');
?>
<header class="admin-topbar">
  <div>
    <h1><?php echo $is_edit ? 'Edit Project' : 'Add Project'; ?></h1>
    <p>Update portfolio details, images and project visibility.</p>
  </div>
  <nav>
    <a href="index.php">Back to Projects</a>
    <a href="logout.php">Logout</a>
  </nav>
</header>

<main class="admin-container">
  <?php if ($error !== ''): ?>
    <div class="admin-alert"><?php echo ya_h($error); ?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="admin-edit-form">
    <section class="admin-card">
      <h2>Project details</h2>

      <div class="admin-form-grid">
        <label>Project ID
          <input name="id" value="<?php echo ya_h(ya_get($project, 'id', '')); ?>" placeholder="client-name">
        </label>

        <label>Project name
          <input name="name" value="<?php echo ya_h(ya_get($project, 'name', '')); ?>" required>
        </label>

        <label>Website URL
          <input name="url" value="<?php echo ya_h(ya_get($project, 'url', '')); ?>" placeholder="https://example.co.uk">
        </label>

        <label>Location
          <input name="location" value="<?php echo ya_h(ya_get($project, 'location', '')); ?>">
        </label>

        <label class="admin-wide">Description
          <textarea name="description" required><?php echo ya_h(ya_get($project, 'description', '')); ?></textarea>
        </label>

        <label class="admin-wide">Services / tags
          <input name="services" value="<?php echo ya_h(ya_array_to_csv(ya_get($project, 'services', array()))); ?>" placeholder="Website Design, Hosting, Local SEO">
        </label>

        <label>Visible
          <select name="visible">
            <option value="1" <?php echo !empty($project['visible']) ? 'selected' : ''; ?>>Yes</option>
            <option value="0" <?php echo empty($project['visible']) ? 'selected' : ''; ?>>No</option>
          </select>
        </label>

        <label>Responsive design
          <select name="responsive">
            <option value="1" <?php echo !empty($project['responsive']) ? 'selected' : ''; ?>>Yes</option>
            <option value="0" <?php echo empty($project['responsive']) ? 'selected' : ''; ?>>No</option>
          </select>
        </label>
      </div>
    </section>

    <section class="admin-card admin-card-compact">
      <h2>Website screenshot</h2>
      <p>Use automatic desktop thumbnails for quick setup, or upload a fixed screenshot for best quality and reliability.</p>

      <div class="screenshot-manager screenshot-manager-simple">
        <div class="screenshot-row screenshot-row-simple">
          <div class="screenshot-label">
            <strong>Desktop preview</strong>
            <span>Main project screenshot</span>
          </div>

          <label>Mode
            <select name="desktopMode">
              <option value="auto" <?php echo ya_get($project, 'desktopMode', 'auto') === 'auto' ? 'selected' : ''; ?>>Auto</option>
              <option value="manual" <?php echo ya_get($project, 'desktopMode', '') === 'manual' ? 'selected' : ''; ?>>Manual</option>
            </select>
          </label>

          <label>Image path
            <input name="desktopThumbnail" value="<?php echo ya_h(ya_get($project, 'desktopThumbnail', '')); ?>">
          </label>

          <label>Upload
            <input type="file" name="desktop" accept="image/png,image/jpeg,image/webp">
          </label>
        </div>
      </div>
    </section>

    <section class="admin-card admin-card-compact">
      <h2>Behind-the-scenes screenshots</h2>
      <p>Use this for admin dashboards, gallery tools or CRM-style systems behind a login. Only upload images that are safe to show publicly.</p>

      <?php
        $adminShots = ya_get($project, 'adminScreenshots', array());
        if (!is_array($adminShots)) {
            $adminShots = array();
        }
      ?>

      <?php if (count($adminShots) > 0): ?>
        <div class="admin-existing-screenshots">
          <?php foreach ($adminShots as $shotIndex => $shot): ?>
            <?php
              if (is_array($shot)) {
                  $shotPath = ya_get($shot, 'image', '');
                  $shotCaption = ya_get($shot, 'caption', '');
              } else {
                  $shotPath = $shot;
                  $shotCaption = '';
              }
            ?>
            <div class="admin-existing-shot">
              <a href="../<?php echo ya_h($shotPath); ?>" target="_blank" rel="noopener">
                <img src="../<?php echo ya_h($shotPath); ?>" alt="Existing admin screenshot <?php echo (int)$shotIndex + 1; ?>">
              </a>

              <input type="hidden" name="keepAdminScreenshots[<?php echo (int)$shotIndex; ?>][image]" value="<?php echo ya_h($shotPath); ?>">

              <label class="admin-delete-shot">
                <input type="checkbox" data-remove-shot>
                Remove
              </label>

              <span class="admin-shot-path"><?php echo ya_h(basename($shotPath)); ?></span>

              <label class="admin-shot-caption">Lightbox description
                <textarea name="keepAdminScreenshots[<?php echo (int)$shotIndex; ?>][caption]" rows="3" placeholder="Short description shown in the website lightbox"><?php echo ya_h($shotCaption); ?></textarea>
              </label>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="admin-muted">No behind-the-scenes screenshots uploaded yet.</p>
      <?php endif; ?>

      <label>Upload admin/backend screenshot
        <input type="file" name="adminScreenshot" accept="image/png,image/jpeg,image/webp">
      </label>

      <label>New screenshot lightbox description
        <textarea name="newAdminScreenshotCaption" rows="3" placeholder="Optional short description for the newly uploaded screenshot"></textarea>
      </label>

      <label>Internal notes (not published)
        <textarea name="notes"><?php echo ya_h(ya_get($project, 'notes', '')); ?></textarea>
      </label>

      <p class="admin-field-help">Notes are stored in MySQL for your own reference only. They are not exposed through the public portfolio API.</p>
    </section>

    <div class="admin-form-actions">
      <button type="submit" class="button button-primary">Save Project</button>
      <?php if ($is_edit): ?>
        <button type="submit" name="delete" value="1" class="admin-danger" onclick="return confirm('Delete this project?')">Delete Project</button>
      <?php endif; ?>
    </div>
  </form>
</main>

<script>
document.addEventListener('change', function (event) {
  if (!event.target || !event.target.matches('[data-remove-shot]')) return;

  var card = event.target.closest('.admin-existing-shot');
  if (!card) return;

  var hidden = card.querySelector('input[type="hidden"][name="keepAdminScreenshots[]"]');
  if (event.target.checked) {
    if (hidden) hidden.disabled = true;
    card.classList.add('is-marked-remove');
  } else {
    if (hidden) hidden.disabled = false;
    card.classList.remove('is-marked-remove');
  }
});
</script>
<?php ya_render_footer(); ?>
