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

// Fonction pour simuler ou exécuter. L'exécution réelle nécessiterait `oc` et `sas-viya` installés
// et configurés dans le conteneur Docker.
$output = "L'exécution réelle des scripts depuis PHP nécessite la configuration des outils CLI (oc, sas-viya) dans le conteneur.\n\nContenu du script :\n\n" . htmlspecialchars($content);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container py-4">
        <a href="index.php" class="btn btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Retour au menu</a>
        
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-terminal me-2"></i><?= htmlspecialchars($title) ?></h5>
                <span class="badge bg-secondary"><?= htmlspecialchars($file) ?></span>
            </div>
            <div class="card-body bg-dark text-light p-0">
                <pre class="m-0 p-3" style="max-height: 70vh; overflow-y: auto;"><code><?= $output ?></code></pre>
            </div>
            <div class="card-footer bg-white">
                <button class="btn btn-primary" onclick="alert('Fonctionnalité d\'exécution en cours de développement !')">
                    <i class="bi bi-play-fill"></i> Exécuter le script
                </button>
            </div>
        </div>
    </div>
</body>
</html>
