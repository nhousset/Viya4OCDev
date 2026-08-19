<?php
$config_path = '/var/www/config.env';
$source_config = file_exists($config_path) ? 'source '.$config_path.' && ' : '';
$script_path = '/var/www/cmd/04_logs_erreurs.sh';
$cmd = "bash -c '{$source_config} export DRY_RUN=false && export PROFILE_NAME=default && bash {$script_path} 2>&1'";
$raw_output = shell_exec($cmd);
$clean_output = preg_replace('/\x1b\[[0-9;]*m/', '', $raw_output);
?>
<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Extraction rapide des logs (Pods en échec/CrashLoop)</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
    <div class='container py-4'>
        <a href='index.php' class='btn btn-outline-secondary mb-3'>Retour</a>
        <div class='card shadow-sm border-0'>
            <div class='card-header bg-dark text-white'><h5>Extraction rapide des logs (Pods en échec/CrashLoop)</h5></div>
            <pre class='m-0 p-3 bg-dark text-light' style='max-height: 75vh; overflow-y: auto;'><?= htmlspecialchars($clean_output) ?></pre>
        </div>
    </div>
</body>
</html>
