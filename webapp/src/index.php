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

$plugins_oc = getScripts($cmd_dir);
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
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background-color: #343a40; color: white; }
        .sidebar-link { color: rgba(255,255,255,.8); text-decoration: none; display: block; padding: 10px 15px; }
        .sidebar-link:hover { color: white; background-color: rgba(255,255,255,.1); }
        .card-menu { cursor: pointer; transition: transform 0.2s; }
        .card-menu:hover { transform: translateY(-5px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
    </style>
</head>
<body>
    <?php require_once 'header_html.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0 pt-3">
                <div class="text-center mb-4">
                    <h5>SAS VIYA 4 OPS</h5>
                    <small>Toolkit</small>
                </div>
                <div class="px-3 mb-2 text-uppercase text-muted small"><strong>OpenShift Plugins</strong></div>
                <?php foreach ($plugins_oc as $index => $script): ?>
                    <a href="<?= pathinfo($script['file'], PATHINFO_FILENAME) ?>.php" class="sidebar-link">
                        <i class="bi bi-terminal me-2"></i><?= htmlspecialchars($script['title']) ?>
                    </a>
                <?php endforeach; ?>

                <div class="px-3 mt-4 mb-2 text-uppercase text-muted small"><strong>Settings</strong></div>
                <a href="config_manager.php" class="sidebar-link text-warning">
                    <i class="bi bi-gear me-2"></i>Profiles & Config
                </a>

                <div class="px-3 mt-4 mb-2 text-uppercase text-muted small"><strong>Custom Views</strong></div>
                <a href="pods_table.php" class="sidebar-link text-info">
                    <i class="bi bi-table me-2"></i>Pods List (Table)
                </a>
                
                <div class="px-3 mt-4 mb-2 text-uppercase text-muted small"><strong>SAS Viya CLI Plugins</strong></div>
                <?php foreach ($plugins_cli as $index => $script): ?>
                    <a href="<?= pathinfo($script['file'], PATHINFO_FILENAME) ?>.php" class="sidebar-link">
                        <i class="bi bi-tools me-2"></i><?= htmlspecialchars($script['title']) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                    <h2>Dashboard</h2>
                    <div>
                        <span class="badge bg-success">Connected</span>
                        <span class="badge bg-primary">Namespace: sas-viya</span>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i> Welcome to the SAS Viya 4 toolkit web interface.
                    Select a plugin from the left menu to execute it.
                </div>

                <h4 class="mb-3 mt-4">Custom Web Views</h4>
                <div class="row g-3">
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm card-menu border-info" onclick="window.location.href='pods_table.php'">
                            <div class="card-body">
                                <h5 class="card-title text-info"><i class="bi bi-table me-2"></i>Pods List</h5>
                                <p class="card-text text-muted small">Executes `oc get pods` and formats the result in a sortable table.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <h4 class="mb-3 mt-5">OpenShift Plugins</h4>
                <div class="row g-3">
                    <?php foreach ($plugins_oc as $script): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 shadow-sm card-menu" onclick="window.location.href='<?= pathinfo($script['file'], PATHINFO_FILENAME) ?>.php'">
                                <div class="card-body">
                                    <h5 class="card-title text-primary"><i class="bi bi-terminal-fill me-2"></i><?= htmlspecialchars($script['title']) ?></h5>
                                    <p class="card-text text-muted small">Script: <?= htmlspecialchars($script['file']) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <h4 class="mb-3 mt-5">Plugins SAS Viya CLI</h4>
                <div class="row g-3">
                    <?php foreach ($plugins_cli as $script): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 shadow-sm card-menu" onclick="window.location.href='<?= pathinfo($script['file'], PATHINFO_FILENAME) ?>.php'">
                                <div class="card-body">
                                    <h5 class="card-title text-purple" style="color: #6f42c1;"><i class="bi bi-tools me-2"></i><?= htmlspecialchars($script['title']) ?></h5>
                                    <p class="card-text text-muted small">Script: <?= htmlspecialchars($script['file']) ?></p>
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
