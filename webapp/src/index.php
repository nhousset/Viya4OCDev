<?php
require_once 'init.php';
$cmd_dir = '/var/www/cmd';
$cmd_cli_dir = '/var/www/cmd_cli';

function getScripts($dir) {
    $scripts = [];
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'sh') {
                $path = $dir . '/' . $file;
                $content = file_get_contents($path);
                $title = $file;
                if (preg_match('/#\s*TITLE:\s*(.*)/', $content, $matches)) {
                    $title = trim($matches[1]);
                }
                $scripts[] = [
                    'file' => $file,
                    'path' => $path,
                    'title' => $title
                ];
            }
        }
    }
    return $scripts;
}

function categorizeScripts($scripts) {
    $categories = [
        'Global Audits & Checks' => [],
        'Resources & Storage' => [],
        'Monitoring & Logs' => [],
        'SAS Viya Components' => [],
        'Network & Deployments' => [],
        'Administration & Operations' => [],
        'Others' => []
    ];

    foreach ($scripts as $s) {
        $name = $s['file'];
        if (preg_match('/^(01|18|19|20|21)_/', $name)) {
            $categories['Global Audits & Checks'][] = $s;
        } elseif (preg_match('/^(02|08|09)_/', $name)) {
            $categories['Resources & Storage'][] = $s;
        } elseif (preg_match('/^(04|10|14|15)_/', $name)) {
            $categories['Monitoring & Logs'][] = $s;
        } elseif (preg_match('/^(03|05|07)_/', $name)) {
            $categories['SAS Viya Components'][] = $s;
        } elseif (preg_match('/^(06|11|12|13)_/', $name)) {
            $categoNetwork & Deployments'][] = $s;
        } elseif (preg_match('/^(16|98)_/', $name)) {
            $categories['Administration & Operations'][] = $s;
        } else {
            $categories['Others'][] = $s;
        }
    }

    return array_filter($categories, function($cat) { return !empty($cat); });
}

$categoryIcons = [
    'Global Audits & Checks' => 'bi-clipboard2-check-fill',
    'Resources & Storage' => 'bi-hdd-stack-fill',
    'Monitoring & Logs' => 'bi-activity',
    'SAS Viya Components' => 'bi-cpu-fill',
    'Network & Deployments' => 'bi-diagram-3-fill',
    'Administration & Operations' => 'bi-gear-fill',
    'Others' => 'bi-terminal-fill'
];

$plugins_oc = getScripts($cmd_dir);
$categorized_oc = categorizeScripts($plugins_oc);

$plugins_cli = getScripts($cmd_cli_dir);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpsBuddy - Copilot Viya 4</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php require_once 'header_html.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0 pt-3 shadow">
                <div class="text-center mb-4">
                    <h5 class="mb-0 fw-bold">OpsBuddy</h5>
                    <small class="text-muted">Toolkit Menu</small>
                </div>
                
                <?php foreach ($categorized_oc as $categoryName => $scripts): ?>
                    <div class="px-3 mt-4 mb-2 text-uppercase fw-bold text-info category-header"><?= htmlspecialchars($categoryName) ?></div>
                    <?php foreach ($scripts as $script): ?>
                        <a href="<?= pathinfo($script['file'], PATHINFO_FILENAME) ?>.php" class="sidebar-link">
                            <i class="bi bi-chevron-right me-1 small"></i><?= htmlspecialchars($script['title']) ?>
                        </a>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                
                <div class="px-3 mt-4 mb-2 text-uppercase fw-bold text-warning category-header">SAS Viya CLI Plugins</div>
                <?php foreach ($plugins_cli as $index => $script): ?>
                    <a href="<?= pathinfo($script['file'], PATHINFO_FILENAME) ?>.php" class="sidebar-link">
                        <i class="bi bi-tools me-2"></i><?= htmlspecialchars($script['title']) ?>
                    </a>
                <?php endforeach; ?>

                <div class="px-3 mt-4 mb-2 text-uppercase fw-bold text-success category-header">Custom Views</div>
                <a href="pods_table.php" class="sidebar-link">
                    <i class="bi bi-table me-2"></i>Pods List (Table)
                </a>
                <a href="03_cas.php" class="sidebar-link">
                    <i class="bi bi-cpu-fill me-2"></i>Manage CAS Engine
                </a>
                
                <?php if (($_SESSION['role'] ?? 'user') === 'admin'): ?>
                <div class="px-3 mt-4 mb-2 text-uppercase fw-bold text-danger category-header">Administration</div>
                <a href="config_manager.php" class="sidebar-link">
                    <i class="bi bi-gear me-2"></i>Profiles & Config
                </a>
                <a href="user_manager.php" class="sidebar-link">
                    <i class="bi bi-people me-2"></i>Users
                </a>
                <?php endif; ?>
                
                <div class="mb-5"></div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                    <h2 class="fw-bold mb-0"><i class="bi bi-grid-1x2-fill me-2 text-primary"></i>Dashboard</h2>
                    <div>
                        <?php if(empty($_SESSION['cluster_connected'][$active_profile])): ?>
                            <button class="btn btn-sm btn-danger px-3 py-2 rounded-pill shadow-sm" onclick="forceConnectionCheck()"><i class="bi bi-plug me-1"></i> Disconnected</button>
                        <?php else: ?>
                            <button class="btn btn-sm btn-success px-3 py-2 rounded-pill shadow-sm" onclick="forceConnectionCheck()"><i class="bi bi-plug-fill me-1"></i> Connected</button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Custom Views -->
                <div class="row mb-5">
                    <div class="col-12">
                        <div class="card shadow-sm border-0 bg-info bg-opacity-10">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h5 class="text-info-emphasis mb-1"><i class="bi bi-table me-2"></i>Custom Web Views</h5>
                                    <p class="mb-0 text-muted small">Native web interfaces optimized for resource analysis.</p>
                                </div>
                                <div>
                                    <a href="pods_table.php" class="btn btn-info text-white shadow-sm px-3 me-2">Open Pods List</a>
                                    <a href="03_cas.php" class="btn btn-primary shadow-sm px-3">Manage CAS Engine</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- OpenShift Plugins Categories -->
                <?php foreach ($categorized_oc as $categoryName => $scripts): ?>
                    <?php if ($categoryName === 'SAS Viya Components'): ?>
                        <?php
                            $scripts = array_filter($scripts, function($s) {
                                return !str_contains($s['file'], '03_cas.sh');
                            });
                        ?>
                    <?php endif; ?>
                    <?php if (empty($scripts)) continue; ?>
                    
                    <?php $catIcon = $categoryIcons[$categoryName] ?? 'bi-terminal-fill'; ?>
                    
                    <h4 class="mb-3 mt-4 text-secondary border-start border-4 border-primary ps-2"><?= htmlspecialchars($categoryName) ?></h4>
                    <div class="row g-3 mb-4">
                        <?php foreach ($scripts as $script): ?>
                            <div class="col-md-6 col-lg-4 col-xl-3">
                                <div class="card h-100 shadow-sm card-menu" onclick="window.location.href='<?= pathinfo($script['file'], PATHINFO_FILENAME) ?>.php'">
                                    <div class="card-body">
                                        <h6 class="card-title text-dark fw-bold mb-2">
                                            <i class="bi <?= $catIcon ?> me-2 text-primary"></i><?= htmlspecialchars($script['title']) ?>
                                        </h6>
                                        <p class="card-text text-muted small mb-0 font-monospace" style="font-size: 0.75rem;">
                                            <i class="bi bi-file-earmark-code me-1"></i><?= htmlspecialchars($script['file']) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <!-- Viya CLI Plugins -->
                <h4 class="mb-3 mt-5 text-secondary border-start border-4 border-warning ps-2">Plugins SAS Viya CLI</h4>
                <div class="row g-3 mb-5">
                    <?php foreach ($plugins_cli as $script): ?>
                        <div class="col-md-6 col-lg-4 col-xl-3">
                            <div class="card h-100 shadow-sm card-menu" onclick="window.location.href='<?= pathinfo($script['file'], PATHINFO_FILENAME) ?>.php'">
                                <div class="card-body">
                                    <h6 class="card-title fw-bold mb-2" style="color: #6f42c1;">
                                        <i class="bi bi-magic me-2"></i><?= htmlspecialchars($script['title']) ?>
                                    </h6>
                                    <p class="card-text text-muted small mb-0 font-monospace" style="font-size: 0.75rem;">
                                        <i class="bi bi-file-earmark-code me-1"></i><?= htmlspecialchars($script['file']) ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </div>
    </div>
    
    <!-- Connection Check Modal -->
    <div class="modal fade" id="connectionModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-dark text-white">
            <h5 class="modal-title"><i class="bi bi-cloud-arrow-down me-2"></i>OpenShift Connection</h5>
          </div>
          <div class="modal-body text-center py-4" id="connModalBody">
            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
            <h5>Checking cluster connection...</h5>
            <p class="text-muted small">Active profile : <?= htmlspecialchars($active_profile) ?></p>
          </div>
          <div class="modal-footer justify-content-center" id="connModalFooter" style="display: none;">
          </div>
        </div>
      </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const connModal = new bootstrap.Modal(document.getElementById('connectionModal'));
        const autoCheck = <?= empty($_SESSION['cluster_connected'][$active_profile]) ? 'true' : 'false' ?>;
        
        if (autoCheck) {
            connModal.show();
            checkConnection();
        }
        
        window.forceConnectionCheck = function() {
            document.getElementById('connModalBody').innerHTML = <diChecking connection...</h5>;
            document.getElementById('connModalFooter').style.display = 'none';
            connModal.show();
            checkConnection();
        };
        
        async function checkConnection() {
            const body = document.getElementById('connModalBody');
            const footer = document.getElementById('connModalFooter');
            
            try {
                const formData = new FormData();
                formData.append('action', 'check');
                const response = await fetch('api_connect.php', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.status === 'config_missing') {
                    body.innerHTML = 
                        <i class="bi bi-exclamation-triangle text-warning mb-3" style="font-size: 3rem;"></i>
                        <h5>Incomplete Configuration</h5>
                        <p class="text-muted">Cluster URL is not configured for this profile.</p>
                    ;
                    footer.innerHTML = <a href="config_manager.php" class="btn btn-primary"><i class="bi bi-gear me-2"></i>Go to Configuration</a>;
                    footer.style.display = 'flex';
                } else if (data.status === 'token_expired') {
                    let linkHtml = '';
                    if (data.token_url && data.token_url !== 'skip') {
                        linkHtml = <a href="+data.token_url+" target="_blank" class="btn btn-sm btn-outline-info mb-3"><i class="bi bi-box-arrow-up-right me-1"></i> Get a new Token</a>;
                    }
                    body.innerHTML = 
                        <i class="bi bi-shield-lock text-danger mb-2" style="font-size: 3rem;"></i>
                        <h5 class="text-danger">Expired or Missing Token</h5>
                        <p class="text-muted small mb-2">Unable to connect to <strong>+data.server+</strong></p>
                        +linkHtml+
                        <div class="form-floating mb-3 text-start">
                          <input type="password" class="form-control" id="newTokenInput" placeholder="sha256~...">
                          <label for="newTokenInput">Paste the new Token here</label>
                        </div>
                    ;
                    footer.innerHTML = <button class="btn btn-success px-4" onclick="saveNewToken()"><i class="bi bi-floppy me-2"></i>Save and Connect</button>
                                        <button class="btn btn-secondary" onclick="window.location.reload()">Close</button>;
                    footer.style.display = 'flex';
                } else if (data.status === 'success') {
                    body.innerHTML = 
                        <i class="bi bi-check-circle text-success mb-3" style="font-size: 3rem;"></i>
                        <h5 class="text-success">Connection Successful</h5>
                        <p class="text-muted small">Successfully connected to the cluster.</p>
                    ;
                    footer.style.display = 'none';
                    setTimeout(() => { window.location.reload(); }, 1000);
                }
            } catch (err) {
                body.innerHTML = <i class="bi bi-x-octagon text-danger mb-3" style="font-size: 3rem;"></i><h5>System Error</h5><p class="text-danger">+err.message+</p>;
                footer.innerHTML = <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>;
                footer.style.display = 'flex';
            }
        }
        
        window.saveNewToken = async function() {
            const tokenVal = document.getElementById('newTokenInput').value.trim();
            if (!tokenVal) return;
            
            const btn = document.querySelector('#connModalFooter .btn-success');
            btn.innerHTML = <span class="spinner-border spinner-border-sm me-2"></span>Saving...;
            btn.disabled = true;
            
            const formData = new FormData();
            formData.append('action', 'save_token');
            formData.append('token', tokenVal);
            
            await fetch('api_connect.php', { method: 'POST', body: formData });
            
            // Retry connection
            document.getElementById('connModalBody').innerHTML = <diChecking connection...</h5>;
            document.getElementById('connModalFooter').style.display = 'none';
            checkConnection();
        };
    });
    </script>
</body>
</html>