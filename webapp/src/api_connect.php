<?php
require_once 'init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    
    function parse_env($path) {
        $vars = [];
        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (preg_match('/^(?:export\s+)?([A-Za-z0-9_]+)="?(.*?)"?$/', $line, $m)) {
                    $vars[$m[1]] = $m[2];
                }
            }
        }
        return $vars;
    }
    
    $env_vars = parse_env($config_path);
    
    if ($action === 'check') {
        $server = $env_vars['SERVER_URL'] ?? '';
        $token = $env_vars['TOKEN'] ?? '';
        $tls = ($env_vars['INSECURE_SKIP_TLS_VERIFY'] ?? 'false') === 'true' ? '--insecure-skip-tls-verify=true' : '';
        $token_url = $env_vars['TOKEN_URL'] ?? '';
        
        if (empty($server)) {
            echo json_encode(['status' => 'config_missing']); exit;
        }
        if (empty($token)) {
            echo json_encode(['status' => 'token_expired', 'token_url' => $token_url, 'server' => $server]); exit;
        }
        
        $cmd = "oc login --server='$server' --token='$token' $tls 2>&1";
        $output = shell_exec($cmd);
        
        if (strpos($output, 'Login successful') !== false || strpos($output, 'Logged into') !== false) {
            $_SESSION['cluster_connected'][$active_profile] = true;
            echo json_encode(['status' => 'success', 'output' => $output]);
        } else {
            $_SESSION['cluster_connected'][$active_profile] = false;
            echo json_encode(['status' => 'token_expired', 'output' => $output, 'token_url' => $token_url, 'server' => $server]);
        }
        exit;
    }
    
    if ($action === 'save_token') {
        $new_token = str_replace('"', '\"', $_POST['token'] ?? '');
        if (!empty($new_token)) {
            $content = file_exists($config_path) ? file_get_contents($config_path) : '';
            if (preg_match('/^(?:export\s+)?TOKEN=.*$/m', $content)) {
                $content = preg_replace('/^(?:export\s+)?TOKEN=.*$/m', "export TOKEN=\"$new_token\"", $content);
            } else {
                $content .= "\nexport TOKEN=\"$new_token\"\n";
            }
            file_put_contents($config_path, $content);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error']);
        }
        exit;
    }
}
?>