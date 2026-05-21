<?php
// Always load the admin files relative to this file. This avoids accidentally using
// duplicate root-level login/config/functions files from older uploads.
require __DIR__ . '/functions.php';

// Re-read the config directly from /admin/config.php so the login check is definitely
// using the intended admin config file.
$config = require __DIR__ . '/config.php';

$error = '';

if (ya_is_logged_in()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)ya_get($_POST, 'username', ''));
    $password = (string)ya_get($_POST, 'password', '');

    if (hash_equals((string)$config['username'], $username) && hash_equals((string)$config['password_sha256'], hash('sha256', $password))) {
        $_SESSION['ya_portfolio_admin'] = true;
        header('Location: index.php');
        exit;
    }

    $error = 'Incorrect username or password.';
}

ya_render_header('Login');
?>
<main class="admin-login">
  <form method="post" class="admin-login-card">
    <img src="../assets/images/yellow-arrow-logo-individual.png" alt="Yellow Arrow Technical Services">
    <h1>Portfolio Admin</h1>
    <p>Sign in to manage portfolio projects and screenshots.</p>

    <?php if ($error !== ''): ?>
      <div class="admin-alert"><?php echo ya_h($error); ?></div>
    <?php endif; ?>

    <label>
      Username
      <input type="text" name="username" autocomplete="username" required>
    </label>

    <label>
      Password
      <input type="password" name="password" autocomplete="current-password" required>
    </label>

    <button type="submit">Log in</button>
  </form>
</main>
<?php ya_render_footer(); ?>
