<?php
session_start();

// Si on est déjà connecté, hop, direction l'accueil
if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$erreur = null;

// Si le formulaire est envoyé
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'config/db.php';
    
    $email = $_POST['email'];
    $password = $_POST['password'];

    // On cherche l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // On vérifie le mot de passe
    if ($user && password_verify($password, $user['password'])) {
        // SUCCÈS : On enregistre l'utilisateur dans la session
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role']
        ];
        header("Location: index.php"); // Redirection vers l'accueil
        exit();
    } else {
        $erreur = "Identifiants incorrects !";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - StageHunter</title>
    <link rel="stylesheet" href="public/style.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; height: 100vh; background: #f4f6f8; font-family: sans-serif; }
        .login-card { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        .error-msg { background: #fee2e2; color: #b91c1c; padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 0.9em; }
        input { width: 100%; padding: 12px; margin: 8px 0 20px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold; }
        button:hover { background: #1d4ed8; }
        .back-link { display: block; margin-top: 20px; color: #666; text-decoration: none; font-size: 0.9em; }
    </style>
</head>
<body>

    <div class="login-card">
        <h2 style="margin-top:0; color:#1e293b;">Connexion 🔐</h2>
        <p style="color:#64748b; margin-bottom:30px;">Accédez à votre espace personnel</p>

        <?php if ($erreur): ?>
            <div class="error-msg"><?php echo $erreur; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div style="text-align: left;">
                <label>Email</label>
                <input type="email" name="email" required placeholder="admin@test.com">
            </div>

            <div style="text-align: left;">
                <label>Mot de passe</label>
                <input type="password" name="password" required placeholder="•••••••">
            </div>

            <button type="submit">Se connecter</button>
        </form>

        <a href="index.php" class="back-link">← Retour au site</a>
    </div>

</body>
</html>