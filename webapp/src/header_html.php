<?php
$build_date_path = '/var/www/build_date.txt';
$build_date = file_exists($build_date_path) ? trim(file_get_contents($build_date_path)) : 'Development Environment';

$config_files_scan = array_filter(glob($app_dir . '/config*.env') ?: [], 'is_file');
if (!in_array($app_dir . '/config.env', $config_files_scan) && !is_dir($app_dir . '/config.env')) {
    array_unshift($config_files_scan, $app_dir . '/config.env');
}
$all_profiles = [];
foreach ($config_files_scan as $f) {
    $base = basename($f);
    if ($base === 'config.env') { $all_profiles[] = 'default'; }
    elseif (preg_match('/config-(.+)\.env/', $base, $m)) { $all_profiles[] = $m[1]; }
}

$my_profiles = [];
if (($_SESSION['role'] ?? 'user') === 'admin') {
    $my_profiles = $all_profiles;
} else {
    $allowed = $_SESSION['allowed_profiles'] ?? [];
    foreach ($all_profiles as $p) {
        if (in_array($p, $allowed)) {
            $my_profiles[] = $p;
        }
    }
}
?>
<style>
    <?php if ($active_env_type === 'prod'): ?>
    body {
        border: 5px solid #dc3545 !important; /* Bootstrap danger red */
        min-height: 100vh;
    }
    <?php endif; ?>
</style>
<nav class="navbar navbar-expand-lg navbar-dark mb-4 shadow-sm" style="background-color: <?= htmlspecialchars($active_header_color) ?> !important;">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-rocket-takeoff me-2"></i>OpsBuddy <?php if ($active_env_type === 'prod'): ?><span class="badge bg-danger ms-2">PROD</span><?php endif; ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
        <?php if (($_SESSION['role'] ?? 'user') === 'admin'): ?>
        <li class="nav-item"><a class="nav-link" href="user_manager.php"><i class="bi bi-people me-1"></i> Users</a></li>
        <li class="nav-item"><a class="nav-link" href="config_manager.php"><i class="bi bi-gear me-1"></i> Config</a></li>
        <?php endif; ?>
        <li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#aboutModal"><i class="bi bi-info-circle me-1"></i> About</a></li>
      </ul>
      
      <div class="d-flex align-items-center me-4">
                    <div class="me-3">
              <?php if ($license_info['valid']): ?>
                  <span class="badge bg-success" title="Expires in <?= $license_info['days_left'] ?> days"><i class="bi bi-patch-check-fill me-1"></i> <?= htmlspecialchars($license_info['client_name']) ?></span>
              <?php else: ?>
                  <a href="license_manager.php" class="badge bg-danger text-decoration-none" title="<?= htmlspecialchars($license_info['reason']) ?>"><i class="bi bi-exclamation-triangle-fill me-1"></i> UNLICENSED</a>
              <?php endif; ?>
          </div>
          <span class="text-white small me-3"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['username'] ?? '') ?></span>
          <form class="m-0 d-flex align-items-center" method="POST">
            <label class="text-white me-2 small fw-bold"><i class="bi bi-person-badge"></i> Profile:</label>
            <select name="switch_profile" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto; min-width: 120px;" <?= empty($my_profiles) ? 'disabled' : '' ?>>
              <?php if (empty($my_profiles)): ?>
                <option>No profile</option>
              <?php else: ?>
                  <?php foreach ($my_profiles as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>" <?= $active_profile === $p ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
                  <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </form>
      </div>
      <a href="logout.php" class="btn btn-sm btn-outline-light"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
  </div>
</nav>

<!-- About Modal -->
<div class="modal fade" id="aboutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title"><i class="bi bi-info-circle me-2"></i>About</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center py-4">
        <img src="img/logo.png" alt="OpsBuddy Logo" style="max-height: 100px; object-fit: contain;" class="mb-3">
        <h4 class="fw-bold">OpsBuddy</h4>
        <p class="text-muted mb-4">Your Viya 4 Copilot on OpenShift</p>
        
                <ul class="list-group list-group-flush text-start mb-3">
          <li class="list-group-item"><i class="bi bi-person-fill me-2 text-primary"></i><strong>Author :</strong> Nicolas Housset</li>
          <li class="list-group-item"><i class="bi bi-globe me-2 text-primary"></i><strong>Website :</strong> <a href="https://nicolas-housset.fr/opsBuddy" target="_blank" class="text-decoration-none">nicolas-housset.fr/opsBuddy</a></li>
          <li class="list-group-item"><i class="bi bi-calendar-event me-2 text-primary"></i><strong>Release Date :</strong> <?= htmlspecialchars($build_date) ?></li>
          <li class="list-group-item">
              <i class="bi bi-shield-check me-2 text-primary"></i><strong>License :</strong> 
              <?php if ($license_info['valid']): ?>
                  <span class="text-success fw-bold"><?= htmlspecialchars($license_info['client_name']) ?></span> (Expires: <?= htmlspecialchars($license_info['expiration_date']) ?>)
              <?php else: ?>
                  <span class="text-danger fw-bold">UNLICENSED</span>
              <?php endif; ?>
          </li>
          <li class="list-group-item text-center bg-light">
              <a href="terms.php" class="text-decoration-none small text-muted"><i class="bi bi-file-earmark-text me-1"></i> View Terms of Service (EULA)</a>
          </li>
        </ul>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>