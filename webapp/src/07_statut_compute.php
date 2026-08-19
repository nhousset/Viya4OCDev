<?php
require_once 'init.php';

$source_config = file_exists($config_path) ? 'source '.$config_path.' && ' : '';
$script_path = '/var/www/cmd/07_statut_compute.sh';

$clean_output = '';
$debug_output = '';
$has_run = false;
$is_debug = false;
$executed_cmd = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_script'])) {
    $is_debug = isset($_POST['debug']) && $_POST['debug'] == '1';
    
    $arg = $is_debug ? 'debug' : '';
    $bash_flag = $is_debug ? '-x' : '';
    
    $out_file = tempnam(sys_get_temp_dir(), 'out_');
    $err_file = tempnam(sys_get_temp_dir(), 'err_');
    
    $cmd = "bash -c '{$source_config} export DRY_RUN=false && export PROFILE_NAME={$active_profile} && bash {$bash_flag} {$script_path} {$arg} >{$out_file} 2>{$err_file}'";
    shell_exec($cmd);
    
    $raw_output = file_get_contents($out_file) ?: '';
    $raw_debug = file_get_contents($err_file) ?: '';
    
    @unlink($out_file);
    @unlink($err_file);
    
    $clean_output = preg_replace('/\x1b\[[0-9;]*m/', '', $raw_output);
    $debug_output = preg_replace('/\x1b\[[0-9;]*m/', '', $raw_debug);
    $has_run = true;
    
    $executed_cmd = "PROFILE_NAME={$active_profile} bash {$bash_flag} {$script_path} {$arg}";
}
?>
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Statut du Serveur Compute (Sessions & Jobs)</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel="stylesheet" href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css'>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</head>
<body class='bg-light'>
    <?php require_once 'header_html.php'; ?>
    <div class='container py-4'>
        <div class='d-flex justify-content-between align-items-center mb-3'>
            <h4 class='m-0'>Statut du Serveur Compute (Sessions & Jobs)</h4>
            <form method='POST' class='m-0 d-flex align-items-center'>
                <input type='hidden' name='run_script' value='1'>
                <div class='form-check me-3'>
                    <input class='form-check-input' type='checkbox' name='debug' value='1' id='debugCheck' <?= $is_debug ? 'checked' : '' ?>>
                    <label class='form-check-label' for='debugCheck'>Debug Mode</label>
                </div>
                <button type='submit' class='btn btn-primary'><i class='bi bi-play-fill'></i> Run Script</button>
            </form>
        </div>

        <?php if ($has_run): ?>
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="result-tab" data-bs-toggle="tab" data-bs-target="#result" type="button" role="tab">Result</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="log-tab" data-bs-toggle="tab" data-bs-target="#log" type="button" role="tab">Execution Log</button>
            </li>
        </ul>
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="result" role="tabpanel">
                <div class='card shadow-sm border-top-0 rounded-0 rounded-bottom'>
                    <pre class='m-0 p-3 bg-dark text-light' style='max-height: 75vh; overflow-y: auto;'><?= htmlspecialchars($clean_output ?: 'No standard output.') ?></pre>
                </div>
            </div>
            <div class="tab-pane fade" id="log" role="tabpanel">
                <div class='card shadow-sm border-top-0 rounded-0 rounded-bottom p-3 bg-white'>
                    <h6>Command executed:</h6>
                    <code class='text-primary d-block mb-3'><?= htmlspecialchars($executed_cmd) ?></code>
                    <h6>Logs & Traces (stderr):</h6>
                    <pre class='m-0 p-3 bg-dark text-light' style='max-height: 60vh; overflow-y: auto;'><?= htmlspecialchars($debug_output ?: 'No debug/error logs.') ?></pre>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info">Click on the "Run Script" button to start the execution.</div>
        <?php endif; ?>
    </div>
</body>
</html>