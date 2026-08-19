<?php
\ = '/var/www/config.env';
\ = file_exists(\) ? 'source '.\.' && ' : '';
\ = '/var/www/cmd/07_statut_compute.sh';
\ = "bash -c '{\} export DRY_RUN=false && export PROFILE_NAME=default && bash {\} 2>&1'";
\ = shell_exec(\);
\ = preg_replace('/\x1b\[[0-9;]*m/', '', \);
?>
<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Statut du Serveur Compute (Sessions & Jobs)</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
    <div class='container py-4'>
        <a href='index.php' class='btn btn-outline-secondary mb-3'>Retour</a>
        <div class='card shadow-sm border-0'>
            <div class='card-header bg-dark text-white'><h5>Statut du Serveur Compute (Sessions & Jobs)</h5></div>
            <pre class='m-0 p-3 bg-dark text-light' style='max-height: 75vh; overflow-y: auto;'><?= htmlspecialchars(\) ?></pre>
        </div>
    </div>
</body>
</html>
