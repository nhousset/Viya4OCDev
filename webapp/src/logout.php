<?php
require_once 'init.php';
log_audit('Logout', 'User logged out');
session_destroy();
header('Location: login.php');
exit;