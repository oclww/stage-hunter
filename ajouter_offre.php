<?php
session_start();
require_once 'config/db.php';

// 1. SÉCURITÉ :  
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'entreprise') {
    die("⛔ Accès interdit. Réservé aux entreprises.");
}

// 2. Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // On doit retrouver l'ID de l'entreprise grâce à l'ID de l'utilisateur connecté
    $stmt = $pdo->prepare("SELECT id FROM companies WHERE user_id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $company = $stmt->fetch();

    if ($company) {
        $sql = "INSERT INTO offers (title, description, salary, contract_type, company_id) VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $pdo->prepare($sql);
        $stmt_insert->execute([
            $_POST['title'],
            $_POST['description'],
            $_POST['salary'],
            $_POST['contract_type'],
            $company['id'] // On utilise l'ID de l'entreprise trouvée
        ]);
        
        header("Location: index.php"); // Retour à l'accueil
        exit();
    } else {
        echo "Erreur : Profil entreprise introuvable.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Publier une offre</title>
    <link rel="stylesheet" href="public/style.css">
    <style>body { font-family: sans-serif; padding: 40px; background: #f4f6f8; } .box { max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 10px; }</style>
</head>
<body>
    <div class="box">
        <h2>📢 Publier une nouvelle offre</h2>
        <form method="POST">
            <label>Titre du poste</label>
            <input type="text" name="title" required style="width:100%; padding:10px; margin-bottom:10px;">
            
            <label>Type de contrat</label>
            <select name="contract_type" style="width:100%; padding:10px; margin-bottom:10px;">
                <option>Alternance</option>
                <option>Stage</option>
                <option>CDI</option>
                <option>CDD</option>
            </select>

            <label>Salaire</label>
            <input type="text" name="salary" placeholder="ex: 1200€" style="width:100%; padding:10px; margin-bottom:10px;">

            <label>Description</label>
            <textarea name="description" rows="5" required style="width:100%; padding:10px; margin-bottom:10px;"></textarea>

            <button type="submit" style="background:#27ae60; color:white; padding:10px 20px; border:none; cursor:pointer;">Publier l'offre ✅</button>
            <a href="index.php" style="margin-left:10px;">Annuler</a>
        </form>
    </div>
</body>
</html>