<?php
session_start();
$active_profile = $_SESSION['active_profile'] ?? 'default';
$config_file = $active_profile === 'default' ? 'config.env' : "config-{$active_profile}.env";
$config_path = "/var/www/app/$config_file";

$source_config = file_exists($config_path) ? 'source '.$config_path.' && ' : '';
$script_path = '/var/www/cmd/02_audit_stockage.sh';

$clean_output = '';
$debug_output = '';
$has_run = false;
$is_debug = false;
$executed_cmd = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    <title>Audit du stockage (PV, PVC, Evénements)</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</head>
<body class='bg-light'>
    <div class='container py-4'>
        <a href='index.php' class='btn btn-outline-secondary mb-3'>Back</a>
        
        <div class='alert alert-secondary py-2'>
            <i class='bi bi-person-badge'></i> Active Profile: <strong><?= htmlspecialchars($active_profile) ?></strong> (<?= htmlspecialchars($config_file) ?>)
            <a href='config_manager.php' class='btn btn-sm btn-outline-dark float-end p-0 px-2'>Change</a>
        </div>
        
        <div class='d-flex justify-content-between align-items-center mb-3'>
            <h4 class='m-0'>Audit du stockage (PV, PVC, Evénements)</h4>
            <form method='POST' class='m-0 d-flex align-items-center'>
                <div class='form-check me-3'>
                    <input class='form-check-input' type='checkbox' name='debug' value='1' id='debugCheck' <?= $is_debug ? 'checked' : '' ?>>
                    <label class='form-check-label' for='debugCheck'>
                        Debug Mode
                    </label>
                </div>
                <button type='submit' class='btn btn-primary'>Run Script</button>
            </form>
        </div>

        <?php if ($has_run): ?>
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="result-tab" data-bs-toggle="tab" data-bs-target="#result" type="button" role="tab" aria-controls="result" aria-selected="true">Result</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="log-tab" data-bs-toggle="tab" data-bs-target="#log" type="button" role="tab" aria-controls="log" aria-selected="false">Execution Log</button>
            </li>
        </ul>
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="result" role="tabpanel" aria-labelledby="result-tab">
                <div class='card shadow-sm border-top-0 rounded-0 rounded-bottom'>
                    <pre class='m-0 p-3 bg-dark text-light' style='max-height: 75vh; overflow-y: auto;'><?= htmlspecialchars($clean_output ?: 'No standard output.') ?></pre>
                </div>
            </div>
            <div class="tab-pane fade" id="log" role="tabpanel" aria-labelledby="log-tab">
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
