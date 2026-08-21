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
        'Audits Globaux & Checks' => [],
        'Ressources & Stockage' => [],
        'Monitoring & Logs' => [],
        'Composants SAS Viya' => [],
        'RÃ©seau & DÃ©ploiements' => [],
        'Administration & OpÃ©rations' => [],
        'Autres' => []
    ];

    foreach ($scripts as $s) {
        $name = $s['file'];
        if (preg_match('/^(01|18|19|20|21)_/', $name)) {
            $categories['Audits Globaux & Checks'][] = $s;
        } elseif (preg_match('/^(02|08|09)_/', $name)) {
            $categories['Ressources & Stockage'][] = $s;
        } elseif (preg_match('/^(04|10|14|15)_/', $name)) {
            $categories['Monitoring & Logs'][] = $s;
        } elseif (preg_match('/^(03|05|07)_/', $name)) {
            $categories['Composants SAS Viya'][] = $s;
        } elseif (preg_match('/^(06|11|12|13)_/', $name)) {
            $categories['RÃ©seau & DÃ©ploiements'][] = $s;
        } elseif (preg_match('/^(16|98)_/', $name)) {
            $categories['Administration & OpÃ©rations'][] = $s;
        } else {
            $categories['Autres'][] = $s;
        }
    }

    return array_filter($categories, function($cat) { return !empty($cat); });
}

$plugins_oc = getScripts($cmd_dir);
$categorized_oc = categorizeScripts($plugins_oc);

$plugins_cli = getScripts($cmd_cli_dir);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAS VIYA 4 OPS - Web UI</title>
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
                    <h5 class="mb-0 fw-bold">SAS VIYA 4 OPS</h5>
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
                        <span class="badge bg-success px-3 py-2 rounded-pill shadow-sm"><i class="bi bi-check-circle me-1"></i> Connected</span>
                    </div>
                </div>

                <!-- Custom Views -->
                <div class="row mb-5">
                    <div class="col-12">
                        <div class="card shadow-sm border-0 bg-info bg-opacity-10">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h5 class="text-info-emphasis mb-1"><i class="bi bi-table me-2"></i>Vues PersonnalisÃ©es Web</h5>
                                    <p class="mb-0 text-muted small">Interfaces web natives optimisÃ©es pour analyser les ressources.</p>
                                </div>
                                <a href="pods_table.php" class="btn btn-info text-white shadow-sm px-4">Ouvrir Pods List</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- OpenShift Plugins Categories -->
                <?php foreach ($categorized_oc as $categoryName => $scripts): ?>
                    <h4 class="mb-3 mt-4 text-secondary border-start border-4 border-primary ps-2"><?= htmlspecialchars($categoryName) ?></h4>
                    <div class="row g-3 mb-4">
                        <?php foreach ($scripts as $script): ?>
                            <div class="col-md-6 col-lg-4 col-xl-3">
                                <div class="card h-100 shadow-sm card-menu" onclick="window.location.href='<?= pathinfo($script['file'], PATHINFO_FILENAME) ?>.php'">
                                    <div class="card-body">
                                        <h6 class="card-title text-dark fw-bold mb-2">
                                            <i class="bi bi-terminal-fill me-2 text-primary"></i><?= htmlspecialchars($script['title']) ?>
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
                                        <i class="bi bi-tools me-2"></i><?= htmlspecialchars($script['title']) ?>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>