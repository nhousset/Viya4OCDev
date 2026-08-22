<?php
require_once 'init.php';

// Only admins can manage config
if (($_SESSION['role'] ?? 'user') !== 'admin') {
    die("Access denied. Admin only.");
}

$app_dir = '/var/www/conf';
$config_files = glob($app_dir . '/config*.env');
if ($config_files === false) $config_files = [];
$config_files = array_filter($config_files, 'is_file');
if (!in_array($app_dir . '/config.env', $config_files) && !is_dir($app_dir . '/config.env')) {
    array_unshift($config_files, $app_dir . '/config.env');
}

$fields_def = [
    'ENV_TYPE' => ['label' => 'Environment Type', 'type' => 'select', 'default' => 'dev', 'options' => [
        'dev' => 'Development',
        'recette' => 'Staging',
        'prod' => 'Production (Red Border)'
    ]],
    'HEADER_COLOR' => ['label' => 'Header Color', 'type' => 'color', 'default' => '#212529'],
    'OC_CLUSTER_URL' => ['label' => 'OpenShift Cluster URL', 'type' => 'text', 'placeholder' => 'https://api.cluster.com:6443'],
    'OC_TOKEN_URL' => ['label' => 'OpenShift Token URL', 'type' => 'text', 'placeholder' => 'Login link or ''skip'''],
    'DEFAULT_NAMESPACE' => ['label' => 'SAS Viya Namespace', 'type' => 'text', 'placeholder' => 'sas-viya'],
    'VIYA_API_URL' => ['label' => 'SAS Viya API URL', 'type' => 'text', 'placeholder' => 'https://viya.mycompany.com'],
    'AUDIT_DIR' => ['label' => 'Audit Output Directory', 'type' => 'text', 'placeholder' => '/var/www/rapports_audit'],
    'IGNORE_SAS_CLI' => ['label' => 'Ignore SAS CLI', 'type' => 'checkbox', 'default' => 'false'],
    'IGNORE_TLS' => ['label' => 'Ignore TLS Verification', 'type' => 'checkbox', 'default' => 'false'],
    'DRY_RUN' => ['label' => 'DRY RUN Mode', 'type' => 'checkbox', 'default' => 'false']
];

function get_profile_name($filename) {
    if ($filename === 'config.env') return 'default';
    if (preg_match('/^config-(.+)\.env$/', $filename, $m)) return $m[1];
    return $filename;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save_file':
            $filename = $_POST['filename'] ?? '';
            if (in_array($app_dir . '/' . $filename, $config_files) || $filename === 'config.env') {
                $vars = $_POST['vars'] ?? [];
                $content = "";
                foreach ($fields_def as $k => $def) {
                    if ($def['type'] === 'checkbox') {
                        $val = isset($vars[$k]) ? 'true' : 'false';
                        $content .= "export {$k}=\"{$val}\"\n";
                    } else {
                        if (isset($vars[$k])) {
                            $val = $vars[$k];
                            $content .= "export {$k}=\"{$val}\"\n";
                        }
                    }
                }
                file_put_contents($app_dir . '/' . $filename, $content);
                $message = "File $filename saved successfully.";
            }
            break;

        case 'delete_profile':
            $filename = $_POST['filename'] ?? '';
            if ($filename !== 'config.env' && preg_match('/^config-[a-zA-Z0-9_-]+\.env$/', $filename)) {
                $path = $app_dir . '/' . $filename;
                if (file_exists($path)) {
                    unlink($path);
                    $message = "Profile $filename deleted successfully.";
                    header("Location: config_manager.php?msg=" . urlencode($message));
                    exit;
                }
            }
            break;
            
        case 'create_profile':
            $new_profile = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['new_profile']);
            if (!empty($new_profile)) {
                $new_filename = 'config-' . strtolower($new_profile) . '.env';
                if (!file_exists($app_dir . '/' . $new_filename)) {
                    $default_content = "export ENV_TYPE=\"dev\"\nexport HEADER_COLOR=\"#212529\"\nexport DEFAULT_NAMESPACE=\"sas-viya\"\nexport DRY_RUN=\"false\"\n";
                    file_put_contents($app_dir . '/' . $new_filename, $default_content);
                    $message = "Profile $new_profile created.";
                    header("Location: config_manager.php?msg=" . urlencode($message));
                    exit;
                } else {
                    $message = "Profile already exists.";
                }
            }
            break;
    }
}
if (isset($_GET['msg'])) { $message = $_GET['msg']; }

function parse_env_file($path) {
    $vars = [];
    if (file_exists($path)) {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^(?:export\s+)?([A-Za-z0-9_]+)="?(.*?)"?$/', $line, $m)) {
                $vars[$m[1]] = $m[2];
            }
        }
    }
    return $vars;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Configuration & Profiles - SAS Viya 4 OPS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <?php require_once 'header_html.php'; ?>
    <div class="container py-4">
        <h2 class="mb-4"><i class="bi bi-gear me-2"></i> Configuration & Profiles</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Create Profile</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="create_profile">
                            <div class="mb-3">
                                <label class="form-label">New Profile Name:</label>
                                <div class="input-group">
                                    <input type="text" name="new_profile" class="form-control" placeholder="e.g. prod" required>
                                    <button class="btn btn-primary" type="submit">Create</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Manage Profiles</h5>
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($config_files as $file): 
                            $base = basename($file);
                            $is_default = ($base === 'config.env');
                        ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>
                                <i class="bi <?= $is_default ? 'bi-star-fill text-warning' : 'bi-file-earmark-text text-secondary' ?> me-2"></i> 
                                <?= htmlspecialchars(get_profile_name($base)) ?>
                            </span>
                            <?php if (!$is_default): ?>
                            <form method="POST" class="m-0" onsubmit="return confirm('Are you sure you want to delete the profile <?= htmlspecialchars(get_profile_name($base)) ?>? This cannot be undone.');">
                                <input type="hidden" name="action" value="delete_profile">
                                <input type="hidden" name="filename" value="<?= htmlspecialchars(get_profile_name($base)) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Profile"><i class="bi bi-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Edit Configuration Fields</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="nav nav-tabs px-3 pt-3 bg-light border-bottom-0" id="configTabs" role="tablist">
                            <?php 
                            $first = true;
                            foreach ($config_files as $file): 
                                $base = basename($file);
                                $id = preg_replace('/[^a-zA-Z0-9]/', '', $base);
                            ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?= $first ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-<?= $id ?>" type="button" role="tab"><?= htmlspecialchars(get_profile_name($base)) ?></button>
                                </li>
                            <?php $first = false; endforeach; ?>
                        </ul>
                        
                        <div class="tab-content p-4" id="configTabsContent">
                            <?php 
                            $first = true;
                            foreach ($config_files as $file): 
                                $base = basename($file);
                                $id = preg_replace('/[^a-zA-Z0-9]/', '', $base);
                                $file_vars = parse_env_file($file);
                            ?>
                                <div class="tab-pane fade <?= $first ? 'show active' : '' ?>" id="tab-<?= $id ?>" role="tabpanel">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="save_file">
                                        <input type="hidden" name="filename" value="<?= htmlspecialchars(get_profile_name($base)) ?>">
                                        
                                        <div class="row g-3">
                                            <?php foreach ($fields_def as $k => $def): 
                                                $val = $file_vars[$k] ?? ($def['default'] ?? '');
                                                $is_check = ($def['type'] === 'checkbox');
                                                $checked = ($val === 'true' || $val === '1') ? 'checked' : '';
                                            ?>
                                                <div class="col-md-6">
                                                    <?php if ($is_check): ?>
                                                        <div class="form-check mt-4">
                                                            <input class="form-check-input" type="checkbox" name="vars[<?= $k ?>]" value="true" id="<?= $id ?>_<?= $k ?>" <?= $checked ?>>
                                                            <label class="form-check-label" for="<?= $id ?>_<?= $k ?>"><?= htmlspecialchars($def['label']) ?></label>
                                                        </div>
                                                    <?php elseif ($def['type'] === 'select'): ?>
                                                        <label class="form-label small fw-bold text-muted mb-1"><?= htmlspecialchars($def['label']) ?></label>
                                                        <select name="vars[<?= $k ?>]" class="form-select form-select-sm">
                                                            <?php foreach ($def['options'] as $opt_val => $opt_label): ?>
                                                                <option value="<?= htmlspecialchars($opt_val) ?>" <?= $val === $opt_val ? 'selected' : '' ?>><?= htmlspecialchars($opt_label) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    <?php elseif ($def['type'] === 'color'): ?>
                                                        <label class="form-label small fw-bold text-muted mb-1"><?= htmlspecialchars($def['label']) ?></label>
                                                        <input type="color" name="vars[<?= $k ?>]" class="form-control form-control-color form-control-sm w-100" value="<?= htmlspecialchars($val) ?>">
                                                    <?php else: ?>
                                                        <label class="form-label small fw-bold text-muted mb-1"><?= htmlspecialchars($def['label']) ?></label>
                                                        <input type="<?= $def['type'] ?>" name="vars[<?= $k ?>]" class="form-control form-control-sm" placeholder="<?= htmlspecialchars($def['placeholder'] ?? '') ?>" value="<?= htmlspecialchars($val) ?>">
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <hr class="my-4">
                                        <div class="text-end">
                                            <button type="submit" class="btn btn-success px-4"><i class="bi bi-floppy me-2"></i> Save Configuration</button>
                                        </div>
                                    </form>
                                </div>
                            <?php $first = false; endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>