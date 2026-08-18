<?php
$type = $_GET['type'] ?? '';
$file = $_GET['file'] ?? '';

$allowed_types = ['oc' => '/var/www/cmd', 'cli' => '/var/www/cmd_cli'];

if (!array_key_exists($type, $allowed_types) || empty($file) || !preg_match('/^[a-zA-Z0-9_-]+\.sh$/', $file)) {
    die("Paramètres invalides.");
}

$dir = $allowed_types[$type];
$path = $dir . '/' . $file;

if (!file_exists($path)) {
    die("Fichier introuvable.");
}

$content = file_get_contents($path);
$title = $file;
if (preg_match('/#\s*TITLE:\s*(.*)/', $content, $matches)) {
    $title = trim($matches[1]);
}

// Exécution du script
$config_path = '/var/www/config.env';
$source_config = file_exists($config_path) ? "source $config_path && " : "";

// On exporte les variables nécessaires (identique au viya.sh)
$cmd = "bash -c '{$source_config} export DRY_RUN=false && export PROFILE_NAME=default && bash {$path} 2>&1'";
$output = shell_exec($cmd);

// Fonction simple pour supprimer les codes de couleur ANSI pour l'affichage HTML
$clean_output = preg_replace('/\x1b\[[0-9;]*m/', '', $output);
if (empty($clean_output)) {
    $clean_output = "Aucune sortie ou erreur lors de l'exécution du script.";
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .terminal-output {
            background-color: #1e1e1e;
            color: #d4d4d4;
            font-family: 'Courier New', Courier, monospace;
            padding: 15px;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 75vh;
            overflow-y: auto;
            border-radius: 0 0 5px 5px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <a href="index.php" class="btn btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Retour au menu</a>
        
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center rounded-top">
                <h5 class="mb-0"><i class="bi bi-terminal me-2"></i><?= htmlspecialchars($title) ?></h5>
                <span class="badge bg-secondary"><?= htmlspecialchars($file) ?></span>
            </div>
            <div class="terminal-output"><?= htmlspecialchars($clean_output) ?></div>
            <div class="card-footer bg-white">
                <button class="btn btn-success" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise"></i> Relancer le script
                </button>
            </div>
        </div>
    </div>
</body>
</html>
