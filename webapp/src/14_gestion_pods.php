<?php
require_once 'init.php';

$script_path = '/var/www/cmd/14_gestion_pods.sh';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_run'])) {
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
    <title>Gestion Interactive des Pods (Recherche, Logs, Shell...)</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel="stylesheet" href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css'>
    <link rel="stylesheet" href="style.css">
</head>
<body class='bg-light'>
    <?php require_once 'header_html.php'; ?>
    <div class='container py-4'>
        <div class='d-flex justify-content-between align-items-center mb-3 bg-white p-3 rounded shadow-sm border'>
            <h4 class='m-0 text-primary'>Gestion Interactive des Pods (Recherche, Logs, Shell...)</h4>
            <form id='runForm' class='m-0 d-flex align-items-center'>
                <div class='form-check me-3'>
                    <input class='form-check-input' type='checkbox' id='debugCheck'>
                    <label class='form-check-label' for='debugCheck'>Debug Mode</label>
                </div>
                <div class='input-group me-3' style='width: 140px;' title="Timeout (secondes)">
                    <span class='input-group-text'><i class="bi bi-stopwatch"></i></span>
                    <input type='number' id='timeoutSec' class='form-control' value='30' min='1' max='300'>
                    <span class='input-group-text'>s</span>
                </div>
                <button type='submit' class='btn btn-primary' id='runBtn'>
                    <i class='bi bi-play-fill'></i> Run
                </button>
            </form>
        </div>

        <div id='loadingIndicator' class='alert alert-info shadow-sm' style='display: none;'>
            <div class='d-flex align-items-center'>
                <div class='spinner-border text-info me-3 loader' role='status'></div>
                <div>
                    <h6 class='mb-1'>Execution in progress...</h6>
                    <small class='text-muted'>Please wait (<span id='timerSpan' class="fw-bold">0</span>s elapsed)</small>
                </div>
            </div>
        </div>

        <div id='errorIndicator' class='alert alert-danger shadow-sm' style='display: none;'></div>

        <div id='outputSection' style='display: none;'>
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="result-tab" data-bs-toggle="tab" data-bs-target="#result" type="button" role="tab">Result</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="log-tab" data-bs-toggle="tab" data-bs-target="#log" type="button" role="tab">Execution Log</button>
                </li>
            </ul>
            <div class="tab-content shadow-sm" id="myTabContent">
                <div class="tab-pane fade show active" id="result" role="tabpanel">
                    <div class='card border-top-0 rounded-0 rounded-bottom'>
                        <pre class='m-0 p-3 bg-dark text-light' style='max-height: 75vh; overflow-y: auto;' id='resultPre'></pre>
                    </div>
                </div>
                <div class="tab-pane fade" id="log" role="tabpanel">
                    <div class='card border-top-0 rounded-0 rounded-bottom p-3 bg-white'>
                        <h6>Command executed:</h6>
                        <code class='text-primary d-block mb-3' id='cmdCode'></code>
                        <h6>Logs & Traces (stderr):</h6>
                        <pre class='m-0 p-3 bg-dark text-light' style='max-height: 60vh; overflow-y: auto;' id='logPre'></pre>
                    </div>
                </div>
            </div>
        </div>
        
        <div id='startPrompt' class="alert alert-secondary border-dashed text-center mt-4">
            <i class="bi bi-terminal fs-3 d-block mb-2 text-muted"></i>
            Click on the <strong>Run</strong> button to start the execution.
        </div>
    </div>
    
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
    <script>
        document.getElementById('runForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('runBtn');
            const loading = document.getElementById('loadingIndicator');
            const outputSec = document.getElementById('outputSection');
            const errorInd = document.getElementById('errorIndicator');
            const startPrompt = document.getElementById('startPrompt');
            
            const isDebug = document.getElementById('debugCheck').checked;
            const timeoutSec = parseInt(document.getElementById('timeoutSec').value) || 30;
            
            btn.disabled = true;
            btn.innerHTML = "<span class='spinner-border spinner-border-sm' role='status' aria-hidden='true'></span> Running...";
            
            loading.style.display = 'block';
            outputSec.style.display = 'none';
            errorInd.style.display = 'none';
            if(startPrompt) startPrompt.style.display = 'none';
            
            let timer = 0;
            document.getElementById('timerSpan').innerText = timer;
            const interval = setInterval(() => {
                timer++;
                document.getElementById('timerSpan').innerText = timer;
            }, 1000);

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), (timeoutSec + 2) * 1000);

            const formData = new FormData();
            formData.append('ajax_run', '1');
            formData.append('timeout', timeoutSec);
            if (isDebug) formData.append('debug', '1');

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    signal: controller.signal
                });
                
                if (!response.ok) throw new Error("HTTP error " + response.status);
                
                const data = await response.json();
                
                let outText = data.clean_output || 'No standard output.';
                document.getElementById('resultPre').textContent = outText;
                document.getElementById('cmdCode').textContent = data.executed_cmd;
                document.getElementById('logPre').textContent = data.debug_output || 'No debug/error logs.';
                
                outputSec.style.display = 'block';
            } catch (err) {
                errorInd.style.display = 'block';
                if (err.name === 'AbortError') {
                    errorInd.innerHTML = '<strong><i class="bi bi-exclamation-triangle"></i> Timeout:</strong> Execution exceeded ' + timeoutSec + ' seconds (Browser Abort).';
                } else {
                    errorInd.innerHTML = '<strong><i class="bi bi-x-circle"></i> Error:</strong> ' + err.message;
                }
            } finally {
                clearInterval(interval);
                clearTimeout(timeoutId);
                btn.disabled = false;
                btn.innerHTML = "<i class='bi bi-play-fill'></i> Run";
                loading.style.display = 'none';
            }
        });
    </script>
</body>
</html>