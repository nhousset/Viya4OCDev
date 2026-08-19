<?php
$config_path = '/var/www/config.env';
$source_config = file_exists($config_path) ? 'source '.$config_path.' && ' : '';
$script_path = '/var/www/cmd/19_audit_scc.sh';
$cmd = "bash -c '{$source_config} export DRY_RUN=false && export PROFILE_NAME=default && bash {$script_path} 2>&1'";

$clean_output = '';
$has_run = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_output = shell_exec($cmd) ?? '';
    $clean_output = preg_replace('/\x1b\[[0-9;]*m/', '', $raw_output);
    $has_run = true;
}
?>
<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Audit des Security Context Constraints (SCC) et permissions</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</head>
<body class='bg-light'>
    <div class='container py-4'>
        <a href='index.php' class='btn btn-outline-secondary mb-3'>Retour</a>
        
        <div class='d-flex justify-content-between align-items-center mb-3'>
            <h4 class='m-0'>Audit des Security Context Constraints (SCC) et permissions</h4>
            <form method='POST' class='m-0'>
                <button type='submit' class='btn btn-primary'>Exécuter le script</button>
            </form>
        </div>

        <?php if ($has_run): ?>
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="result-tab" data-bs-toggle="tab" data-bs-target="#result" type="button" role="tab" aria-controls="result" aria-selected="true">Résultat</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="log-tab" data-bs-toggle="tab" data-bs-target="#log" type="button" role="tab" aria-controls="log" aria-selected="false">Log d'exécution</button>
            </li>
        </ul>
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="result" role="tabpanel" aria-labelledby="result-tab">
                <div class='card shadow-sm border-top-0 rounded-0 rounded-bottom'>
                    <pre class='m-0 p-3 bg-dark text-light' style='max-height: 75vh; overflow-y: auto;'><?= htmlspecialchars($clean_output) ?></pre>
                </div>
            </div>
            <div class="tab-pane fade" id="log" role="tabpanel" aria-labelledby="log-tab">
                <div class='card shadow-sm border-top-0 rounded-0 rounded-bottom p-3 bg-white'>
                    <h6>Commande exécutée en arrière-plan :</h6>
                    <code class='text-primary'><?= htmlspecialchars($cmd) ?></code>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info">Cliquez sur le bouton "Exécuter" pour lancer l'audit.</div>
        <?php endif; ?>
    </div>
</body>
</html>
