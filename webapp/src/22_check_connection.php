<?php
require_once 'init.php';

$script_path = '/var/www/cmd/22_check_connection.sh';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_run'])) {
    log_audit('Execute Script', 'Script: 22_check_connection.php');
    header('Content-Type: application/json');
    $is_debug = isset($_POST['debug']) && $_POST['debug'] == '1';
    $timeout_sec = (int)($_POST['timeout'] ?? 30);
    if ($timeout_sec <= 0) $timeout_sec = 30;

    $source_config = file_exists($config_path) ? 'source '.$config_path.' && ' : '';
    $arg = $is_debug ? 'debug' : '';
    $bash_flag = $is_debug ? '-x' : '';
    
    $out_file = tempnam(sys_get_temp_dir(), 'out_');
    $err_file = tempnam(sys_get_temp_dir(), 'err_');
    
    $cmd = "timeout {$timeout_sec}s bash -c '{$source_config} export DRY_RUN=false && export PROFILE_NAME={$active_profile} && bash {$bash_flag} {$script_path} {$arg} >{$out_file} 2>{$err_file}'";
    
    shell_exec($cmd);
    
    $raw_output = file_get_contents($out_file) ?: '';
    $raw_debug = file_get_contents($err_file) ?: '';
    
    @unlink($out_file);
    @unlink($err_file);
    
    $clean_output = preg_replace('/\x1b\[[0-9;]*m/', '', $raw_output);
    $debug_output = preg_replace('/\x1b\[[0-9;]*m/', '', $raw_debug);
    
    $executed_cmd = "timeout {$timeout_sec}s PROFILE_NAME={$active_profile} bash {$bash_flag} {$script_path} {$arg}";
    
    echo json_encode([
        'status' => 'success',
        'clean_output' => $clean_output,
        'debug_output' => $debug_output,
        'executed_cmd' => $executed_cmd
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>OpenShift Connection Diagnostics - OpsBuddy</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css'>
    <link rel='stylesheet' href='style.css'>
</head>
<body class='bg-light'>
    <?php require_once 'header_html.php'; ?>
    <div class='container py-4'>
        <div class='d-flex justify-content-between align-items-center mb-3 bg-white p-3 rounded shadow-sm border'>
            <h4 class='m-0 text-primary'>OpenShift Connection Diagnostics</h4>
            <form id='runForm' class='m-0 d-flex align-items-center'>
                <div class='form-check me-3'>
                    <input class='form-check-input' type='checkbox' id='debugCheck'>
                    <label class='form-check-label' for='debugCheck'>Debug Mode</label>
                </div>
                <div class='input-group me-3' style='width: 140px;' title="Timeout (seconds)">
                    <span class='input-group-text'><i class="bi bi-stopwatch"></i></span>
                    <input type='number' id='timeoutSec' class='form-control' value='30' min='1' max='300'>
                    <span class='input-group-text'>s</span>
                </div>
                <button type='submit' class='btn btn-primary' id='runBtn'>
                    <i class='bi bi-play-fill'></i> Run
                </button>
            </form>
        </div>

        <div id='loader' class='text-center my-5' style='display:none;'>
            <div class='spinner-border text-primary' role='status' style='width: 3rem; height: 3rem;'></div>
            <p class='mt-2 text-muted'>Running diagnostics...</p>
        </div>

        <div id='resultArea' style='display:none;'>
            <div class='card shadow-sm mb-4'>
                <div class='card-header bg-dark text-white d-flex justify-content-between align-items-center'>
                    <span><i class='bi bi-terminal me-2'></i>Standard Output</span>
                    <button class='btn btn-sm btn-outline-light copy-btn' data-target='stdoutPre'>Copy</button>
                </div>
                <div class='card-body p-0'>
                    <pre id='stdoutPre' class='m-0 p-3 bg-dark text-light border-0' style='max-height: 500px; overflow-y: auto;'></pre>
                </div>
            </div>
            
            <div class='card shadow-sm border-danger' id='debugCard' style='display:none;'>
                <div class='card-header bg-danger text-white d-flex justify-content-between align-items-center'>
                    <span><i class='bi bi-bug me-2'></i>Debug / Error Output</span>
                    <button class='btn btn-sm btn-outline-light copy-btn' data-target='stderrPre'>Copy</button>
                </div>
                <div class='card-body p-0'>
                    <pre id='stderrPre' class='m-0 p-3 bg-dark text-light border-0' style='max-height: 300px; overflow-y: auto;'></pre>
                </div>
            </div>
            
            <div class='text-muted small mt-2'>
                <strong>Command executed:</strong> <code id='cmdExecuted'></code>
            </div>
        </div>
    </div>

    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
    <script>
    document.getElementById('runForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('runBtn');
        const loader = document.getElementById('loader');
        const resultArea = document.getElementById('resultArea');
        const isDebug = document.getElementById('debugCheck').checked ? '1' : '0';
        const timeoutSec = document.getElementById('timeoutSec').value;
        
        btn.disabled = true;
        loader.style.display = 'block';
        resultArea.style.display = 'none';
        
        const formData = new FormData();
        formData.append('ajax_run', '1');
        formData.append('debug', isDebug);
        formData.append('timeout', timeoutSec);
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('stdoutPre').textContent = data.clean_output || 'No output.';
            
            const debugCard = document.getElementById('debugCard');
            if (data.debug_output && data.debug_output.trim() !== '') {
                document.getElementById('stderrPre').textContent = data.debug_output;
                debugCard.style.display = 'block';
            } else {
                debugCard.style.display = 'none';
            }
            
            document.getElementById('cmdExecuted').textContent = data.executed_cmd;
            
            loader.style.display = 'none';
            resultArea.style.display = 'block';
            btn.disabled = false;
        })
        .catch(err => {
            alert('An error occurred during execution.');
            loader.style.display = 'none';
            btn.disabled = false;
        });
    });

    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const text = document.getElementById(targetId).textContent;
            navigator.clipboard.writeText(text).then(() => {
                const originalText = this.textContent;
                this.textContent = 'Copied!';
                setTimeout(() => { this.textContent = originalText; }, 2000);
            });
        });
    });
    </script>
</body>
</html>