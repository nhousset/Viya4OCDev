<?php
session_start();
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if (isset($_SESSION['must_change_password']) && $_SESSION['must_change_password']) {
        header('Location: change_password.php'); exit;
    }
    header('Location: index.php'); exit;
}

$users_file = '/var/www/conf/users.json';
if (!file_exists($users_file)) {
    $default_users = [
        'admin' => [
            'password' => password_hash('admin', PASSWORD_DEFAULT),
            'role' => 'admin',
            'profiles' => ['*'],
            'must_change_password' => true
        ]
    ];
    @file_put_contents($users_file, json_encode($default_users, JSON_PRETTY_PRINT));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $users = @json_decode(@file_get_contents($users_file), true) ?: [];
    
    // Fallback in case users.json could not be created (e.g. permission issues)
    if (empty($users)) {
        $users['admin'] = [
            'password' => password_hash('admin', PASSWORD_DEFAULT),
            'role' => 'admin',
            'profiles' => ['*'],
            'must_change_password' => true
        ];
    }
    
    if (isset($users[$username]) && password_verify($password, $users[$username]['password'])) {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $users[$username]['role'];
        $_SESSION['allowed_profiles'] = $users[$username]['profiles'] ?? [];
        
        // Force reset if admin is still using 'admin' as password
        if ($username === 'admin' && $password === 'admin') {
            $users[$username]['must_change_password'] = true;
        }
        
        if (!empty($users[$username]['must_change_password'])) {
            $_SESSION['must_change_password'] = true;
            header('Location: change_password.php'); exit;
        }
        
        header('Location: index.php'); exit;
    } else {
        $error = 'Identifiants incorrects.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - SAS Viya 4 OPS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">
    <div class="card shadow" style="width: 400px;">
        <div class="card-header bg-dark text-white text-center py-3">
            <h4 class="m-0"><i class="bi bi-lock-fill me-2"></i>Secure Access</h4>
        </div>
        <div class="card-body p-4">
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
        </div>
    </div>
</body>
</html>