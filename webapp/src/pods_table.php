<?php
// Chargement de la configuration pour avoir l'URL, Token, Namespace, etc.
$config_path = '/var/www/config.env';
$source_config = file_exists($config_path) ? "source $config_path && " : "";

// Commande pour récupérer les pods au format JSON
// On passe par bash pour sourcer config.env afin d'avoir le bon contexte (TOKEN, etc.)
// Note: Dans un environnement réel avec un namespace par défaut, on l'utilise.
$cmd = "bash -c '{$source_config} oc get pods -n \${DEFAULT_NAMESPACE:-sas-viya} -o json 2>/dev/null'";
$output = shell_exec($cmd);

$pods_data = json_decode($output, true);
$pods = $pods_data['items'] ?? [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pods List (Table)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="bi bi-table me-2"></i>OpenShift Pods List</h4>
            <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Pod Name</th>
                            <th>Status</th>
                            <th>Pod IP</th>
                            <th>Node</th>
                            <th>Age (Created)</th>
                            <th>Restarts</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pods)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    No pods found or unable to connect to the cluster.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pods as $pod): 
                                $name = $pod['metadata']['name'] ?? 'N/A';
                                $status = $pod['status']['phase'] ?? 'Unknown';
                                $podIP = $pod['status']['podIP'] ?? '-';
                                $node = $pod['spec']['nodeName'] ?? '-';
                                $creationTimestamp = $pod['metadata']['creationTimestamp'] ?? null;
                                
                                // Calcul de l'âge
                                $age = '-';
                                if ($creationTimestamp) {
                                    $date = new DateTime($creationTimestamp);
                                    $now = new DateTime();
                                    $interval = $now->diff($date);
                                    if ($interval->d > 0) $age = $interval->d . "j";
                                    elseif ($interval->h > 0) $age = $interval->h . "h";
                                    else $age = $interval->i . "m";
                                }
                                
                                // Calcul des redémarrages
                                $restarts = 0;
                                if (isset($pod['status']['containerStatuses'])) {
                                    foreach ($pod['status']['containerStatuses'] as $cStatus) {
                                        $restarts += $cStatus['restartCount'] ?? 0;
                                    }
                                }

                                // Couleur du badge de statut
                                $badgeClass = 'bg-secondary';
                                if ($status === 'Running' || $status === 'Succeeded') $badgeClass = 'bg-success';
                                elseif ($status === 'Failed' || $status === 'Error') $badgeClass = 'bg-danger';
                                elseif ($status === 'Pending') $badgeClass = 'bg-warning text-dark';
                            ?>
                            <tr>
                                <td class="font-monospace small"><?= htmlspecialchars($name) ?></td>
                                <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span></td>
                                <td><?= htmlspecialchars($podIP) ?></td>
                                <td><?= htmlspecialchars($node) ?></td>
                                <td><?= htmlspecialchars($age) ?></td>
                                <td>
                                    <?php if ($restarts > 0): ?>
                                        <span class="text-danger fw-bold"><?= $restarts ?></span>
                                    <?php else: ?>
                                        0
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
