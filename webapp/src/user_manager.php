<?php
require_once 'init.php';

if (($_SESSION['role'] ?? 'user') !== 'admin') {
    die("Access denied.");
}

$all_profiles = [];
$config_files_scan = array_filter(glob('/var/www/conf/config*.env') ?: [], 'is_file');
foreach ($config_files_scan as $f) {
    $base = basename($f);
    if ($base === 'config.env') { $all_profiles[] = 'default'; }
    elseif (preg_match('/config-(.+)\.env$/', $base, $m)) { $all_profiles[] = $m[1]; }
}

$users_file = '/var/www/conf/users.json';
$users = @json_decode(@file_get_contents($users_file), true) ?: [];
if (empty($users) || !isset($users['admin'])) {
    $users['admin'] = [
        'password' => password_hash('admin', PASSWORD_DEFAULT),
        'role' => 'admin',
        'profiles' => ['*']
    ];
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_user') {
            $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username']);
            $password = $_POST['password'] ?? '';
            $profiles = $_POST['profiles'] ?? [];
            $force_change = isset($_POST['force_change']);
            
            if (!empty($username)) {
                if (!isset($users[$username])) {
                    // New user
                    if (empty($password)) {
                        $error = "Password is required for new users.";
                    } else {
                        $users[$username] = [
                            'password' => password_hash($password, PASSWORD_DEFAULT),
                            'role' => 'user',
                            'profiles' => $profiles
                        ];
                        if ($force_change) $users[$username]['must_change_password'] = true;
                        
                        if (@file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT)) !== false) {
                            $message = "User $username created successfully."; log_audit('Create User', 'User '.$username); 
                        } else {
                            $error = "Failed to save users.json.";
                        }
                    }
                } else {
                    // Edit existing user
                    if (!empty($password)) {
                        $users[$username]['password'] = password_hash($password, PASSWORD_DEFAULT);
                    }
                    if ($username !== 'admin') {
                        $users[$username]['profiles'] = $profiles;
                    }
                    if ($force_change) {
                        $users[$username]['must_change_password'] = true;
                    } else {
                        unset($users[$username]['must_change_password']);
                    }
                    
                    if (@file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT)) !== false) {
                        $message = "User $username updated successfully."; log_audit('Update User', 'User '.$username); 
                    } else {
                        $error = "Failed to save users.json.";
                    }
                }
            }
        } elseif ($_POST['action'] === 'delete_user') {
            $username = $_POST['username'];
            if ($username !== 'admin' && isset($users[$username])) {
                unset($users[$username]);
                if (@file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT)) !== false) {
                    $message = "User $username deleted."; log_audit('Delete User', 'User '.$username); 
                } else {
                    $error = "Failed to save users.json.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Manager - OpsBuddy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
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
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

                <ul class="nav nav-tabs mb-4" id="userTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold" id="manage-tab" data-bs-toggle="tab" data-bs-target="#manage" type="button" role="tab"><i class="bi bi-people me-2"></i>Manage Users</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="audit-tab" data-bs-toggle="tab" data-bs-target="#audit" type="button" role="tab"><i class="bi bi-journal-text me-2"></i>Audit Logs</button>
            </li>
        </ul>

        <div class="tab-content" id="userTabsContent">
            <!-- MANAGE USERS TAB -->
            <div class="tab-pane fade show active" id="manage" role="tabpanel">
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Add / Edit User</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="userForm">
                            <input type="hidden" name="action" value="add_user">
                            <div class="mb-3">
                                <label class="form-label">Username:</label>
                                <input type="text" name="username" id="form_username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password:</label>
                                <input type="password" name="password" id="form_password" class="form-control">
                                <div class="form-text">Leave blank to keep current password when editing.</div>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" name="force_change" id="form_force_change">
                                <label class="form-check-label text-danger fw-bold" for="form_force_change">Force password change on next login</label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Allowed Profiles:</label>
                                <select name="profiles[]" id="form_profiles" class="form-select" multiple size="4">
                                    <?php foreach ($all_profiles as $p): ?>
                                        <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Hold Ctrl/Cmd to select multiple. Admin ignores this.</div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Save User</button>
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
                                    <th>Status</th>
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
                                        <?php if (!empty($data['must_change_password'])): ?>
                                            <span class="badge bg-warning text-dark"><i class="bi bi-key-fill me-1"></i>Must change PWD</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">OK</span>
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
                                    <td class="align-middle text-end">
                                        <?php
                                            $safeProfiles = htmlspecialchars(json_encode($data['profiles'] ?? []));
                                            $safeForce = !empty($data['must_change_password']) ? 'true' : 'false';
                                        ?>
                                        <button class="btn btn-sm btn-outline-primary me-1" title="Edit User" onclick="editUser('<?= htmlspecialchars($u) ?>', <?= $safeProfiles ?>, <?= $safeForce ?>)"><i class="bi bi-pencil"></i></button>
                                        <?php if ($u !== 'admin'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete user <?= htmlspecialchars($u) ?>?');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="username" value="<?= htmlspecialchars($u) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
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
        </div> <!-- End Manage Users Tab -->

            <!-- AUDIT LOGS TAB -->
            <div class="tab-pane fade" id="audit" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-search me-2"></i>Audit Trail Search</h5>
                        <input type="text" id="auditSearch" class="form-control form-control-sm w-25" placeholder="Search logs (user, action, IP...)">
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                            <table class="table table-hover table-striped m-0" id="auditTable">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Details</th>
                                        <th>Profile</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $audit_files = glob('/var/www/conf/audit/*.json') ?: [];
                                    $all_logs = [];
                                    foreach ($audit_files as $file) {
                                        $u = str_replace(['log_', '.json'], '', basename($file));
                                        $logs = @json_decode(file_get_contents($file), true) ?: [];
                                        foreach ($logs as $l) {
                                            $l['user'] = $u;
                                            $all_logs[] = $l;
                                        }
                                    }
                                    usort($all_logs, function($a, $b) {
                                        return strtotime($b['time']) - strtotime($a['time']);
                                    });
                                    
                                    if (empty($all_logs)): ?>
                                        <tr><td colspan="6" class="text-center py-4 text-muted">No audit logs found.</td></tr>
                                    <?php else:
                                        foreach ($all_logs as $log): ?>
                                        <tr>
                                            <td class="text-nowrap small"><?= htmlspecialchars($log['time'] ?? '') ?></td>
                                            <td class="fw-bold text-primary"><?= htmlspecialchars($log['user'] ?? '') ?></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($log['action'] ?? '') ?></span></td>
                                            <td class="small"><?= htmlspecialchars($log['details'] ?? '') ?></td>
                                            <td><span class="badge bg-info text-dark"><?= htmlspecialchars($log['profile'] ?? '') ?></span></td>
                                            <td class="text-muted small"><?= htmlspecialchars($log['ip'] ?? '') ?></td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div> <!-- End Audit Logs Tab -->
        </div> <!-- End Tab Content -->
    </div>

        <script>
    function editUser(username, profiles, forceChange) {
        document.getElementById('form_username').value = username;
        document.getElementById('form_password').value = '';
        document.getElementById('form_force_change').checked = forceChange;
        
        const select = document.getElementById('form_profiles');
        for (let i = 0; i < select.options.length; i++) {
            select.options[i].selected = profiles.includes(select.options[i].value);
        }
        window.scrollTo(0, 0);
        document.getElementById('form_password').focus();
    }

    document.getElementById('auditSearch').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#auditTable tbody tr');
        
        rows.forEach(row => {
            let text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>