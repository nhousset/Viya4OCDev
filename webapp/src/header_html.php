<?php
$config_files_scan = array_filter(glob($app_dir . '/config*.env') ?: [], 'is_file');
if (!in_array($app_dir . '/config.env', $config_files_scan) && !is_dir($app_dir . '/config.env')) {
    array_unshift($config_files_scan, $app_dir . '/config.env');
}
$profiles = [];
foreach ($config_files_scan as $f) {
    $base = basename($f);
    if ($base === 'config.env') { $profiles[] = 'default'; }
    elseif (preg_match('/config-(.+)\.env/', $base, $m)) { $profiles[] = $m[1]; }
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-rocket-takeoff me-2"></i>SAS Viya 4 OPS</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="config_manager.php"><i class="bi bi-gear me-1"></i> Admin / Config</a></li>
      </ul>
      <form class="d-flex align-items-center me-4 m-0" method="POST">
        <label class="text-light me-2 small fw-bold"><i class="bi bi-person-badge"></i> Profile:</label>
        <select name="switch_profile" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto; min-width: 120px;">
          <?php foreach ($profiles as $p): ?>
            <option value="<?= htmlspecialchars($p) ?>" <?= $active_profile === $p ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
      <a href="logout.php" class="btn btn-sm btn-outline-light"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
  </div>
</nav>