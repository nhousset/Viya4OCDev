<?php
require_once 'license_helper.php';
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
        header('Location: login.php');
        exit;
    }
}

if (isset($_SESSION['must_change_password']) && $_SESSION['must_change_password'] === true) {
    if (basename($_SERVER['PHP_SELF']) !== 'change_password.php' && basename($_SERVER['PHP_SELF']) !== 'logout.php') {
        header('Location: change_password.php');
        exit;
    }
}

$app_dir = '/var/www/conf';
$role = $_SESSION['role'] ?? 'user';

// RBAC protection
$admin_pages = ['config_manager.php', 'user_manager.php'];
if (in_array(basename($_SERVER['PHP_SELF']), $admin_pages) && $role !== 'admin') {
    header('Location: index.php');
    exit;
}

function log_audit($action, $details = '') {
    $username = $_SESSION['username'] ?? 'system';
    $audit_dir = '/var/www/conf/audit';
    if (!is_dir($audit_dir)) {
        @mkdir($audit_dir, 0777, true);
    }
    
    $file = $audit_dir . '/log_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $username) . '.json';
    $logs = [];
    if (file_exists($file)) {
        $logs = @json_decode(file_get_contents($file), true) ?: [];
    }
    
    // Add new entry at the beginning
    array_unshift($logs, [
        'time' => date('Y-m-d H:i:s'),
        'action' => $action,
        'details' => $details,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'profile' => $_SESSION['active_profile'] ?? 'none'
    ]);
    
    if (count($logs) > 1000) {
        $logs = array_slice($logs, 0, 1000);
    }
    @file_put_contents($file, json_encode($logs, JSON_PRETTY_PRINT));
}

$allowed_profiles = $_SESSION['allowed_profiles'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['switch_profile'])) {
    $requested_profile = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['switch_profile']);
    if ($role === 'admin' || in_array($requested_profile, $allowed_profiles)) {
        $_SESSION['active_profile'] = $requested_profile;
        log_audit('Switch Profile', "Profile changed to: $requested_profile");
    }
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

if (!isset($_SESSION['active_profile'])) {
    if ($role === 'admin') {
        $_SESSION['active_profile'] = 'default';
    } else {
        $default_prof = $_SESSION['default_profile'] ?? null;
        if (!empty($default_prof) && in_array($default_prof, $allowed_profiles)) {
            $_SESSION['active_profile'] = $default_prof;
        } else {
            $_SESSION['active_profile'] = !empty($allowed_profiles) ? $allowed_profiles[0] : 'default';
        }
    }
} else {
    if ($role !== 'admin' && !in_array($_SESSION['active_profile'], $allowed_profiles)) {
         $_SESSION['active_profile'] = !empty($allowed_profiles) ? $allowed_profiles[0] : 'default';
    }
}

$active_profile = $_SESSION['active_profile'] ?? 'default';
$config_file = $active_profile === 'default' ? 'config.env' : "config-{$active_profile}.env";
$config_path = "/var/www/conf/{$config_file}";

$active_env_type = '';
$active_header_color = '#212529'; // Bootstrap bg-dark default

if (file_exists($config_path)) {
    $lines = file($config_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (preg_match('/^(?:export\s+)?ENV_TYPE="?(.*?)"?$/', $line, $m)) {
            $active_env_type = $m[1];
        }
        if (preg_match('/^(?:export\s+)?HEADER_COLOR="?(.*?)"?$/', $line, $m)) {
            $active_header_color = $m[1];
        }
    }
}
?>