<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
        header('Location: login.php');
        exit;
    }
}

$app_dir = '/var/www/app';
$role = $_SESSION['role'] ?? 'user';

// RBAC protection
$admin_pages = ['config_manager.php', 'user_manager.php'];
if (in_array(basename($_SERVER['PHP_SELF']), $admin_pages) && $role !== 'admin') {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['switch_profile'])) {
    $requested_profile = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['switch_profile']);
    if ($role === 'admin' || in_array($requested_profile, $_SESSION['allowed_profiles'])) {
        $_SESSION['active_profile'] = $requested_profile;
    }
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

if (!isset($_SESSION['active_profile'])) {
    if ($role === 'admin') {
        $_SESSION['active_profile'] = 'default';
    } else {
        $_SESSION['active_profile'] = !empty($_SESSION['allowed_profiles']) ? $_SESSION['allowed_profiles'][0] : 'default';
    }
} else {
    if ($role !== 'admin' && !in_array($_SESSION['active_profile'], $_SESSION['allowed_profiles'])) {
         $_SESSION['active_profile'] = !empty($_SESSION['allowed_profiles']) ? $_SESSION['allowed_profiles'][0] : 'default';
    }
}

$active_profile = $_SESSION['active_profile'] ?? 'default';
$config_file = $active_profile === 'default' ? 'config.env' : "config-{$active_profile}.env";
$config_path = "/var/www/app/{$config_file}";
?>