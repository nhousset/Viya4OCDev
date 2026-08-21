<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php'); exit;
}

$error = '';
$users_file = '/var/www/conf/users.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password) || $new_password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas ou sont vides.";
    } elseif (strlen($new_password) < 5) {
        $error = "Le mot de passe doit faire au moins 5 caractÃ¨res.";
    } else {
        $users = @json_decode(@file_get_contents($users_file), true) ?: [];
        $username = $_SESSION['username'];
        
        if (!isset($users[$username])) {
            // Utilisateur n'existe pas physiquement (cas du fallback)
            $users[$username] = [
                'role' => $_SESSION['role'],
                'profiles' => $_SESSION['allowed_profiles']
            ];
        }
        
        // Mise Ã  jour
        $users[$username]['password'] = password_hash($new_password, PASSWORD_DEFAULT);
        unset($users[$username]['must_change_password']);
        
        if (@file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT)) !== false) {
            unset($_SESSION['must_change_password']);
            header('Location: index.php'); exit;
        } else {
            $error = "Erreur: Impossible de sauvegarder le fichier users.json dans /var/www/conf/. VÃ©rifiez les permissions du volume.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Changer le mot de passe - SAS Viya 4 OPS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">
    <div class="card shadow" style="width: 400px;">
        <div class="card-header bg-danger text-white text-center py-3">
            <h5 class="m-0"><i class="bi bi-shield-lock me-2"></i>Changement de mot de passe requis</h5>
        </div>
        <div class="card-body p-4">
            <p class="text-muted small text-center mb-4">Pour des raisons de sÃ©curitÃ©, vous devez personnaliser votre mot de passe lors de la premiÃ¨re connexion.</p>
            
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Nouveau mot de passe</label>
                    <input type="password" name="new_password" class="form-control" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label">Confirmer le mot de passe</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-danger w-100">Enregistrer et Continuer</button>
            </form>
        </div>
    </div>
</body>
</html>