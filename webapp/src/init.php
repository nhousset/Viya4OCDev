<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
        header('Location: login.php');
        exit;
    }
}
$app_dir = '/var/www/app';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['switch_profile'])) {
    $_SESSION['active_profile'] = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['switch_profile']);
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}
$active_profile = $_SESSION['active_profile'] ?? 'default';
$config_file = $active_profile === 'default' ? 'config.env' : "config-{$active_profile}.env";
$config_path = "/var/www/app/{$config_file}";
?>