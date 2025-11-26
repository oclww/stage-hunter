<?php
require_once 'config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $offer_id = intval($_POST['offer_id']);
    $name = htmlspecialchars($_POST['candidate_name']);
    $email = htmlspecialchars($_POST['candidate_email']);
    $message = htmlspecialchars($_POST['message']);
    $cv_path = NULL; // Par défaut, pas de CV

    // --- GESTION UPLOAD CV ---
    if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] == 0) {
        
        $info_fichier = pathinfo($_FILES['cv_file']['name']);
        $extension = strtolower($info_fichier['extension']);
        
        // 1. Sécurité : On n'accepte QUE les PDF
        if ($extension === 'pdf') {
            
            // 2. On génère un nom unique (ex: cv_65a4b_dupont.pdf)
            $nom_unique = "cv_" . uniqid() . "_" . preg_replace('/[^a-z0-9]/', '', strtolower($name)) . "." . $extension;
            $destination = "uploads/" . $nom_unique;

            // 3. On déplace le fichier
            if (move_uploaded_file($_FILES['cv_file']['tmp_name'], $destination)) {
                $cv_path = $destination; // C'est ce qu'on va stocker en BDD
            } else {
                die("❌ Erreur lors de l'enregistrement du fichier sur le serveur.");
            }
        } else {
            die("❌ Format incorrect. Seuls les fichiers PDF sont acceptés.");
        }
    }
    // --- FIN GESTION CV ---

    // Insertion en BDD avec le CV
    try {
        $sql = "INSERT INTO applications (offer_id, candidate_name, candidate_email, message, cv_file) 
                VALUES (:offer_id, :name, :email, :message, :cv)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':offer_id' => $offer_id,
            ':name' => $name,
            ':email' => $email,
            ':message' => $message,
            ':cv' => $cv_path
        ]);

    } catch (PDOException $e) {
        die("Erreur BDD : " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Succès</title>
    <link rel="stylesheet" href="public/style.css">
    <style> body { display: flex; justify-content: center; align-items: center; height: 100vh; text-align: center; } </style>
</head>
<body>
    <div style="background:white; padding:40px; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.1);">
        <h1 style="color:#27ae60;">Candidature envoyée ! 📂</h1>
        <p>Merci <?php echo $name; ?>, votre CV a bien été transmis.</p>
        <a href="index.php" class="btn">Retour aux offres</a>
    </div>
</body>
</html>