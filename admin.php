<?php
session_start();

// 1. SÉCURITÉ 
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/db.php';

// 2. On récupère toutes les candidatures avec le titre de l'offre associée
$sql = "SELECT applications.*, offers.title as offer_title 
        FROM applications 
        JOIN offers ON applications.offer_id = offers.id 
        ORDER BY applications.created_at DESC";

$stmt = $pdo->query($sql);
$candidatures = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace Admin - StageHunter</title>
    <link rel="stylesheet" href="public/style.css">
    <style>
        
        body { background: #f8f9fa; }
        .admin-container { max-width: 1000px; margin: 40px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f1f5f9; color: #2c3e50; }
        tr:hover { background-color: #f8f9fa; }
        
        .empty-msg { text-align: center; color: #7f8c8d; padding: 40px; }
        .btn-logout { float: right; background: #e74c3c; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-size: 14px; }
    </style>
</head>
<body>

    <div class="admin-container">
        <a href="logout.php" class="btn-logout">Déconnexion</a>
        <a href="index.php" class="btn-logout" style="background:#3498db; margin-right:10px;">Voir le site</a>
        
        <h1>Tableau de Bord 👔</h1>
        <p>Bienvenue Admin. Voici les candidatures reçues.</p>

        <?php if (empty($candidatures)): ?>
            <div class="empty-msg">Aucune candidature pour le moment 😴</div>
        <?php else: ?>
            
            <table>
                <thead>
                    <th>CV</th>
                    <tr>
                        <th>Date</th>
                        <th>Candidat</th>
                        <th>Offre visée</th>
                        <th>Email</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($candidatures as $c): ?>
                        <tr>
                            <td><?php echo date('d/m H:i', strtotime($c['created_at'])); ?></td>
                            <td style="font-weight:bold;"><?php echo htmlspecialchars($c['candidate_name']); ?></td>
                            <td><span class="badge"><?php echo htmlspecialchars($c['offer_title']); ?></span></td>
                            <td><?php echo htmlspecialchars($c['candidate_email']); ?></td>
                            <td style="font-size:0.9em; color:#555;"><?php echo htmlspecialchars($c['message']); ?></td>
                            <td>
                <?php if (!empty($c['cv_file'])): ?>
                    <a href="<?php echo $c['cv_file']; ?>" target="_blank" style="color:#2563eb; font-weight:bold;">📥 Télécharger</a>
                <?php else: ?>
                    <span style="color:#999;">Pas de CV</span>
                <?php endif; ?>
            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>
    </div>

</body>
</html>