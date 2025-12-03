<?php
session_start();
require_once 'config/db.php';

// 1. SÉCURITÉ : Vérifier que c'est bien une ENTREPRISE
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'entreprise') {
    header("Location: login.php");
    exit();
}

// 2. RÉCUPÉRER L'ID DE L'ENTREPRISE CONNECTÉE
// On sait qui est connecté (user_id), mais on doit trouver son ID d'entreprise (company_id)
$stmt = $pdo->prepare("SELECT id, name FROM companies WHERE user_id = ?");
$stmt->execute([$_SESSION['user']['id']]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    die("Erreur : Profil entreprise introuvable.");
}

$company_id = $company['id'];

// 3. LA REQUÊTE CIBLÉE (Le cœur du système)
// On sélectionne les candidatures
// -> On fait une jointure avec les OFFRES
// -> On filtre pour ne garder que les offres de CETTE entreprise ($company_id)
$sql = "SELECT applications.*, offers.title as offer_title 
        FROM applications 
        JOIN offers ON applications.offer_id = offers.id 
        WHERE offers.company_id = ? 
        ORDER BY applications.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$company_id]);
$candidatures = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes Candidatures</title>
    <link rel="stylesheet" href="public/style.css">
    <style>
        body { background: #f8f9fa; font-family: sans-serif; padding: 20px; }
        .dashboard-box { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #ddd; vertical-align: middle; }
        th { background-color: #f1f5f9; color: #2c3e50; }
        .empty-msg { text-align: center; padding: 40px; color: #777; font-style: italic; }
        .btn-action { display: inline-block; padding: 5px 10px; border-radius: 5px; text-decoration: none; font-size: 0.9em; margin-right: 5px; }
        .btn-cv { background: #eef2ff; color: #2563eb; font-weight: bold; }
        .btn-cv:hover { background: #dbeafe; }
    </style>
</head>
<body>

    <div class="dashboard-box">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h1 style="margin-bottom:5px;">Espace Recruteur 👔</h1>
                <p style="color:#666; margin-top:0;">Entreprise : <strong><?php echo htmlspecialchars($company['name']); ?></strong></p>
            </div>
            <div>
                <a href="ajouter_offre.php" class="btn-action" style="background:#27ae60; color:white;">+ Nouvelle Offre</a>
                <a href="index.php" class="btn-action" style="background:#3498db; color:white;">Retour au site</a>
            </div>
        </div>

        <h3>Candidatures reçues pour vos offres</h3>

        <?php if (empty($candidatures)): ?>
            <div class="empty-msg">
                Vous n'avez reçu aucune candidature pour le moment.
            </div>
        <?php else: ?>
            
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Candidat</th>
                        <th>Poste concerné</th>
                        <th>Message</th>
                        <th>CV (PDF)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($candidatures as $c): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($c['created_at'])); ?></td>
                            
                            <td>
                                <strong><?php echo htmlspecialchars($c['candidate_name']); ?></strong><br>
                                <a href="mailto:<?php echo htmlspecialchars($c['candidate_email']); ?>" style="font-size:0.85em; color:#666;">
                                    <?php echo htmlspecialchars($c['candidate_email']); ?>
                                </a>
                            </td>
                            
                            <td>
                                <span style="background:#fff3cd; color:#856404; padding:3px 8px; border-radius:10px; font-size:0.85em;">
                                    <?php echo htmlspecialchars($c['offer_title']); ?>
                                </span>
                            </td>
                            
                            <td style="color:#555; font-size:0.9em; max-width: 300px;">
                                <?php echo nl2br(htmlspecialchars($c['message'])); ?>
                            </td>
                            
                            <td>
                                <?php if (!empty($c['cv_file'])): ?>
                                    <a href="<?php echo htmlspecialchars($c['cv_file']); ?>" target="_blank" class="btn-action btn-cv">
                                        📥 Voir le CV
                                    </a>
                                <?php else: ?>
                                    <span style="color:#ccc;">Aucun</span>
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