<?php
require_once 'init.php';

$app_dir = '/var/www/app';
$config_files = glob($app_dir . '/config*.env');
if ($config_files === false) $config_files = [];
$config_files = array_filter($config_files, 'is_file');
if (!in_array($app_dir . '/config.env', $config_files) && !is_dir($app_dir . '/config.env')) {
    array_unshift($config_files, $app_dir . '/config.env');
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'save_file':
            $filename = basename($_POST['filename']);
            if (preg_match('/^config.*\.env$/', $filename)) {
                file_put_contents($app_dir . '/' . $filename, $_POST['content']);
                $message = "File $filename saved successfully.";
            }
            break;
            
        case 'create_profile':
            $new_profile = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['new_profile']);
            if (!empty($new_profile)) {
                $new_filename = 'config-' . strtolower($new_profile) . '.env';
                if (!file_exists($app_dir . '/' . $new_filename)) {
                    $default_content = "DEFAULT_NAMESPACE=sas-viya\nDRY_RUN=false\n";
                    file_put_contents($app_dir . '/' . $new_filename, $default_content);
                    $message = "Profile $new_profile created.";
                    $config_files[] = $app_dir . '/' . $new_filename;
                    // Reload the page to refresh the header
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Configuration & Profiles - SAS Viya 4 OPS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <?php require_once 'header_html.php'; ?>
    <div class="container py-4">
        <h2><i class="bi bi-gear me-2"></i> Configuration & Profiles</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="row mt-4">
            <!-- Profile Creation -->
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
            </div>

            <!-- File Editor -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Edit Configuration Files</h5>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="configTabs" role="tablist">
                            <?php 
                            $first = true;
                            foreach ($config_files as $file): 
                                $base = basename($file);
                                $id = preg_replace('/[^a-zA-Z0-9]/', '', $base);
                            ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?= $first ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-<?= $id ?>" type="button" role="tab"><?= $base ?></button>
                                </li>
                            <?php $first = false; endforeach; ?>
                        </ul>
                        
                        <div class="tab-content border border-top-0 p-3" id="configTabsContent">
                            <?php 
                            $first = true;
                            foreach ($config_files as $file): 
                                $base = basename($file);
                                $id = preg_replace('/[^a-zA-Z0-9]/', '', $base);
                                $content = file_exists($file) ? file_get_contents($file) : '';
                            ?>
                                <div class="tab-pane fade <?= $first ? 'show active' : '' ?>" id="tab-<?= $id ?>" role="tabpanel">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="save_file">
                                        <input type="hidden" name="filename" value="<?= htmlspecialchars($base) ?>">
                                        <div class="mb-3">
                                            <textarea name="content" class="form-control font-monospace" rows="15"><?= htmlspecialchars($content) ?></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-success"><i class="bi bi-floppy"></i> Save <?= $base ?></button>
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