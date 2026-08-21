<?php
require_once 'init.php';

$users_file = '/var/www/conf/users.json';
$users = json_decode(@file_get_contents($users_file), true) ?: [];

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

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_user') {
            $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username']);
            $password = $_POST['password'];
            $profiles = $_POST['profiles'] ?? [];
            
            if (!empty($username) && !empty($password)) {
                $users[$username] = [
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => 'user',
                    'profiles' => $profiles
                ];
                file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));
                $message = "User $username added/updated successfully.";
            }
        } elseif ($_POST['action'] === 'delete_user') {
            $username = $_POST['username'];
            if ($username !== 'admin' && isset($users[$username])) {
                unset($users[$username]);
                file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));
                $message = "User $username deleted.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Manager - SAS Viya 4 OPS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <?php require_once 'header_html.php'; ?>
    <div class="container py-4">
        <h2 class="mb-4"><i class="bi bi-people me-2"></i> User Manager</h2>
        
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
                        <h5 class="mb-0">Add / Edit User</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="add_user">
                            <div class="mb-3">
                                <label class="form-label">Username:</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password:</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Allowed Profiles:</label>
                                <select name="profiles[]" class="form-select" multiple size="4">
                                    <?php foreach ($all_profiles as $p): ?>
                                        <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Hold Ctrl/Cmd to select multiple.</div>
                            </div>
                            <button class="btn btn-primary w-100" type="submit">Save User</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Existing Users</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover m-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Allowed Profiles</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u => $data): ?>
                                <tr>
                                    <td class="align-middle fw-bold"><?= htmlspecialchars($u) ?></td>
                                    <td class="align-middle">
                                        <?php if ($data['role'] === 'admin'): ?>
                                            <span class="badge bg-danger">Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="align-middle">
                                        <?php if ($data['role'] === 'admin'): ?>
                                            <span class="badge bg-dark">ALL</span>
                                        <?php else: ?>
                                            <?php foreach (($data['profiles'] ?? []) as $p): ?>
                                                <span class="badge bg-info text-dark"><?= htmlspecialchars($p) ?></span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end align-middle">
                                        <?php if ($u !== 'admin'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete user?');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="username" value="<?= htmlspecialchars($u) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>