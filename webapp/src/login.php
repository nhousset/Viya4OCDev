<?php
require_once 'init.php';
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if (isset($_SESSION['must_change_password']) && $_SESSION['must_change_password']) {
        header('Location: change_password.php'); exit;
    }
    log_audit('Login', 'Successful login'); header('Location: index.php'); exit;
}

$users_file = '/var/www/conf/users.json';
$users = @json_decode(@file_get_contents($users_file), true) ?: [];

// Add default admin if doesn't exist
if (empty($users) || !isset($users['admin'])) {
    $users['admin'] = [
        'password' => password_hash('admin', PASSWORD_DEFAULT),
        'role' => 'admin',
        'profiles' => ['*']
    ];
    @file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (isset($users[$username]) && password_verify($password, $users[$username]['password'])) {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $users[$username]['role'];
        $_SESSION['allowed_profiles'] = $users[$username]['profiles'] ?? [];
        $_SESSION['default_profile'] = $users[$username]['default_profile'] ?? null;
        
        // Force reset if admin is still using 'admin' as password
        if ($username === 'admin' && $password === 'admin') {
            $users[$username]['must_change_password'] = true;
        }
        
        if (!empty($users[$username]['must_change_password'])) {
            $_SESSION['must_change_password'] = true;
            header('Location: change_password.php'); exit;
        }
        
        log_audit('Login', 'Successful login'); header('Location: index.php'); exit;
    } else {
        $error = 'Invalid credentials.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - OpsBuddy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background-color: #f5f6f7;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: none;
        }
        .login-logo {
            max-width: 250px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-card card">
        <div class="card-body p-5 text-center">
            <img src="img/logo.png" alt="OpsBuddy Logo" class="login-logo">
            <h4 class="mb-4 text-muted">Sign In</h4>
            
            <?php if ($error): ?>
                <div class="alert alert-danger p-2"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3 text-start">
                    <label class="form-label text-muted small fw-bold">USERNAME</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>
                <div class="mb-4 text-start">
                    <label class="form-label text-muted small fw-bold">PASSWORD</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">Log In</button>
            </form>
        </div>
    </div>
</body>
</html>