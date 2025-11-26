<?php
// 1. On vérifie si l'URL contient un ID (ex: id=2)
if (!isset($_GET['id'])) {
    // Si pas d'ID, on renvoie vers l'accueil
    header('Location: index.php');
    exit();
}

$id_offre = $_GET['id'];

// 2. Connexion à la Base de Données
require_once 'config/db.php';

// 3. On va chercher les infos de CETTE offre précise
// On utilise une "Requête Préparée" (le ?) pour la sécurité
$sql = "SELECT offers.*, companies.name AS company_name, companies.city, companies.description AS company_desc 
        FROM offers 
        JOIN companies ON offers.company_id = companies.id 
        WHERE offers.id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_offre]);
$offre = $stmt->fetch(PDO::FETCH_ASSOC);

// Si l'offre n'existe pas, on arrête
if (!$offre) {
    die("Cette offre n'existe pas !");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($offre['title']); ?></title>
    <link rel="stylesheet" href="public/style.css"> 
    <style>
        /* Petit rappel CSS au cas où le fichier externe ne charge pas */
        body { font-family: sans-serif; background: #f4f4f9; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; }
        .badge { background: #dbeafe; color: #1e40af; padding: 5px 10px; border-radius: 15px; font-weight: bold; }
        h1 { color: #2c3e50; }
        .btn { background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px;}
    </style>
</head>
<body>

    <div class="container">
        <a href="index.php">← Retour aux offres</a>
        
        <div style="margin-top: 20px;">
            <span class="badge"><?php echo htmlspecialchars($offre['contract_type']); ?></span>
            <h1><?php echo htmlspecialchars($offre['title']); ?></h1>
            <p>
                Chez <strong><?php echo htmlspecialchars($offre['company_name']); ?></strong> 
                • <?php echo htmlspecialchars($offre['city']); ?>
            </p>
        </div>

        <hr style="margin: 30px 0; border: 0; border-top: 1px solid #eee;">

        <h3>À propos du poste</h3>
        <p>
            <?php echo nl2br(htmlspecialchars($offre['description'])); ?>
        </p>

        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 20px;">
            <strong>💰 Salaire :</strong> <?php echo htmlspecialchars($offre['salary']); ?>
        </div>

        <h3>À propos de l'entreprise</h3>
        <p><?php echo htmlspecialchars($offre['company_desc']); ?></p>

        <div style="margin-top: 40px; border-top: 1px solid #eee; padding-top: 20px;">
            <h3>Intéressé ?</h3>
            <form action="postuler.php" method="POST" enctype="multipart/form-data">
                
                <input type="hidden" name="offer_id" value="<?php echo $offre['id']; ?>">

                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom:5px;">Votre Nom & Prénom</label>
                    <input type="text" name="candidate_name" required style="width: 100%; padding: 10px;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom:5px;">Votre Email</label>
                    <input type="email" name="candidate_email" required style="width: 100%; padding: 10px;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom:5px;">Votre CV (PDF uniquement)</label>
                    <input type="file" name="cv_file" accept=".pdf" required style="background:white; padding:10px; width:100%;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom:5px;">Message de motivation</label>
                    <textarea name="message" rows="4" style="width: 100%; padding: 10px;"></textarea>
                </div>

                <button type="submit" class="btn">Envoyer ma candidature 🚀</button>
            </form>

    </div>

</body>
</html>