<?php
require_once 'init.php';

// Only admins can upload a license
if (($_SESSION['role'] ?? 'user') !== 'admin') {
    die("Access denied. Admin only.");
}

$message = '';
$error = '';
$lic_path = '/var/www/conf/license.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['license_file'])) {
    if ($_FILES['license_file']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['license_file']['tmp_name'];
        $file_content = file_get_contents($tmp_name);
        
        // Basic validation before saving
        $data = @json_decode($file_content, true);
        if ($data && isset($data['signature']) && isset($data['client_name']) && isset($data['expiration_date'])) {
            // Attempt to save
            if (@file_put_contents($lic_path, $file_content)) {
                $message = "License file uploaded and saved successfully.";
                // Refresh license info globally
                $license_info = get_license_info();
            } else {
                $error = "Failed to save the license file. Check folder permissions in /var/www/conf/.";
            }
        } else {
            $error = "The uploaded file is not a valid OpsBuddy license file.";
        }
    } else {
        $error = "File upload error.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>License Management - OpsBuddy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <?php require_once 'header_html.php'; ?>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-shield-check me-2"></i> License Management</h2>
            <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white fw-bold"><i class="bi bi-info-circle me-2"></i>Current License Status</div>
                    <div class="card-body">
                        <?php if ($license_info['valid']): ?>
                            <div class="alert alert-success">
                                <h4 class="alert-heading"><i class="bi bi-check-circle-fill me-2"></i>Valid License</h4>
                                <hr>
                                <p class="mb-1"><strong>Client Name:</strong> <?= htmlspecialchars($license_info['client_name']) ?></p>
                                <p class="mb-1"><strong>Client ID:</strong> <?= htmlspecialchars($license_info['client_id']) ?></p>
                                <p class="mb-1"><strong>Expiration Date:</strong> <?= htmlspecialchars($license_info['expiration_date']) ?></p>
                                <p class="mb-0"><strong>Days Remaining:</strong> <?= $license_info['days_left'] ?> days</p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <h4 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Unlicensed or Expired</h4>
                                <hr>
                                <p class="mb-0"><strong>Reason:</strong> <?= htmlspecialchars($license_info['reason']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold"><i class="bi bi-upload me-2"></i>Upload New License</div>
                    <div class="card-body">
                        <p class="text-muted small">Select the .json license file provided by your vendor to activate OpsBuddy.</p>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">License File</label>
                                <input type="file" name="license_file" class="form-control" accept=".json,.lic" required>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-cloud-arrow-up me-1"></i> Upload and Apply</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>