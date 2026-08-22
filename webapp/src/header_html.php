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

// Current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    /* SAS Viya Layout Styles */
    body {
        padding-top: 50px;
        padding-left: 250px;
    }
    .viya-topbar {
        position: fixed;
        top: 0; left: 0; right: 0;
        height: 50px;
        background-color: <?= $active_header_color === '#212529' ? '#005b9f' : htmlspecialchars($active_header_color) ?> !important;
        z-index: 1030;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 20px;
    }
    .viya-sidebar {
        position: fixed;
        top: 50px; left: 0; bottom: 0;
        width: 250px;
        background-color: #f8f9fa;
        border-right: 1px solid #dee2e6;
        overflow-y: auto;
        z-index: 1020;
    }
    <?php if ($active_env_type === 'prod'): ?>
    body {
        border: 4px solid #dc3545 !important;
    }
    .viya-topbar {
        background-color: #dc3545 !important;
    }
    <?php endif; ?>
</style>

<!-- TOPBAR -->
<div class="viya-topbar text-white shadow-sm">
    <div class="d-flex align-items-center">
        <a href="#" class="text-white text-decoration-none me-4 fs-5"><i class="bi bi-list"></i></a>
        <a class="text-white text-decoration-none fw-bold" href="index.php">OpsBuddy - Manage Environment</a>
        <?php if ($active_env_type === 'prod'): ?><span class="badge bg-white text-danger ms-3">PROD</span><?php endif; ?>
    </div>
    
    <div class="d-flex align-items-center">
        <div class="me-3">
            <?php if ($license_info['valid']): ?>
                <span class="badge bg-success" title="Expires in <?= $license_info['days_left'] ?> days"><i class="bi bi-patch-check-fill me-1"></i> <?= htmlspecialchars($license_info['client_name']) ?></span>
            <?php else: ?>
                <a href="license_manager.php" class="badge bg-danger text-decoration-none" title="<?= htmlspecialchars($license_info['reason']) ?>"><i class="bi bi-exclamation-triangle-fill me-1"></i> UNLICENSED</a>
            <?php endif; ?>
        </div>
        
        <form class="m-0 d-flex align-items-center me-3" method="POST">
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
        
        <div class="dropdown">
            <a href="#" class="text-white text-decoration-none dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
                <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center me-2" style="width:28px;height:28px;font-weight:bold;">
                    <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><h6 class="dropdown-header"><?= htmlspecialchars($_SESSION['username'] ?? '') ?></h6></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#aboutModal"><i class="bi bi-info-circle me-2"></i>About OpsBuddy</a></li>
                <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- SIDEBAR -->
<div class="viya-sidebar pt-3 shadow-sm">
    <div class="px-3 mb-2 text-uppercase fw-bold category-header">System</div>
    
    <a href="index.php" class="sidebar-link <?= $current_page === 'index.php' ? 'active' : '' ?>">
        <i class="bi bi-speedometer2 me-2"></i>Dashboard
    </a>
    <?php if (($_SESSION['role'] ?? 'user') === 'admin'): ?>
    <a href="user_manager.php" class="sidebar-link <?= $current_page === 'user_manager.php' ? 'active' : '' ?>">
        <i class="bi bi-people me-2"></i>Users
    </a>
    <a href="config_manager.php" class="sidebar-link <?= $current_page === 'config_manager.php' ? 'active' : '' ?>">
        <i class="bi bi-gear me-2"></i>Configuration
    </a>
    <?php endif; ?>

    <?php foreach ($categorized_oc as $categoryName => $scripts): ?>
        <div class="px-3 mt-4 mb-2 text-uppercase fw-bold category-header"><?= htmlspecialchars($categoryName) ?></div>
        <?php foreach ($scripts as $script): 
            $script_page = pathinfo($script['file'], PATHINFO_FILENAME) . '.php';
            $is_active = ($current_page === $script_page);
        ?>
            <a href="<?= $script_page ?>" class="sidebar-link <?= $is_active ? 'active' : '' ?>">
                <i class="bi bi-<?= $is_active ? 'record-circle-fill' : 'circle' ?> me-2 small"></i><?= htmlspecialchars($script['title']) ?>
            </a>
        <?php endforeach; ?>
    <?php endforeach; ?>
    
    <div class="px-3 mt-4 mb-2 text-uppercase fw-bold category-header">Viya CLI Plugins</div>
    <?php foreach ($plugins_cli as $script): 
        $script_page = pathinfo($script['file'], PATHINFO_FILENAME) . '.php';
        $is_active = ($current_page === $script_page);
    ?>
        <a href="<?= $script_page ?>" class="sidebar-link <?= $is_active ? 'active' : '' ?>">
            <i class="bi bi-tools me-2"></i><?= htmlspecialchars($script['title']) ?>
        </a>
    <?php endforeach; ?>

    <div class="px-3 mt-4 mb-2 text-uppercase fw-bold category-header">Custom Views</div>
    <a href="pods_table.php" class="sidebar-link <?= $current_page === 'pods_table.php' ? 'active' : '' ?>">
        <i class="bi bi-table me-2"></i>Pods List
    </a>
    <a href="03_cas.php" class="sidebar-link <?= $current_page === '03_cas.php' ? 'active' : '' ?>">
        <i class="bi bi-cpu-fill me-2"></i>Manage CAS
    </a>
    
    <!-- Spacer -->
    <div class="pb-5"></div>
</div>

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