<?php
session_start();
require_once 'config/db.php';

// --- LOGIQUE DE RECHERCHE ---

// 1. On prépare la base de la requête
$sql = "SELECT offers.*, companies.name AS company_name, companies.city 
        FROM offers 
        JOIN companies ON offers.company_id = companies.id 
        WHERE 1=1"; // Astuce : "WHERE 1=1" permet d'ajouter des "AND" facilement après

$params = [];

// 2. Est-ce qu'on cherche un mot clé ? (Titre de l'offre)
if (!empty($_GET['q'])) {
    $sql .= " AND offers.title LIKE ?";
    $params[] = "%" . $_GET['q'] . "%"; // Les % veulent dire "contient ce mot"
}

// 3. Est-ce qu'on cherche une ville ?
if (!empty($_GET['ville'])) {
    $sql .= " AND companies.city LIKE ?";
    $params[] = "%" . $_GET['ville'] . "%";
}

// 4. On finit par trier par date
$sql .= " ORDER BY created_at DESC";

// 5. On exécute la requête avec les filtres
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$offres = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StageHunter</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>

    <nav>
        <div class="logo">StageHunter 🚀</div>
        <div class="liens">

            <?php if (isset($_SESSION['user'])): ?>
                
                <span style="font-weight: bold; margin-right:10px;">
                    Bonjour <?php echo htmlspecialchars($_SESSION['user']['role']); ?> 👋
                </span>

                <?php if ($_SESSION['user']['role'] == 'entreprise'): ?>
                    <a href="dashboard.php" class="bouton-login" style="background-color: #8e44ad; margin-right: 5px;">Mes Candidats</a>
                    <a href="ajouter_offre.php" class="bouton-login" style="background-color: #f39c12; margin-right: 5px;">+ Offre</a>
                <?php endif; ?>

                <?php if ($_SESSION['user']['role'] == 'admin'): ?>
                    <a href="admin.php" class="bouton-login" style="background-color: #27ae60; margin-right: 5px;">Admin Global</a>
                <?php endif; ?>
                
                <a href="logout.php" class="bouton-login" style="background-color: #e74c3c;">Déconnexion</a>

            <?php else: ?>
                
                <a href="register.php" class="bouton-login" style="background-color: #3498db; margin-right: 5px;">Inscription</a>
                <a href="login.php" class="bouton-login">Connexion</a>

            <?php endif; ?>
        </div>
        </div>
    </nav>

    <header class="hero">
        <h1>Décroche ton alternance de rêve</h1>
        <p>Des centaines d'offres pour les étudiants en tech, marketing et business.</p>
        <form action="index.php" method="GET" class="search-box">
            <input type="text" name="q" placeholder="Ex: Développeur, Marketing..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
            
            <input type="text" name="ville" placeholder="Ex: Paris, Lyon..." value="<?php echo isset($_GET['ville']) ? htmlspecialchars($_GET['ville']) : ''; ?>">
            
            <button type="submit">Rechercher</button>
        </form>
    </header>

    <section class="container">
        <h2>Les dernières offres 🔥</h2>
        
        <div class="offres-grid">
            <?php foreach ($offres as $offre): ?>
                <div class="carte-offre">
                    <div class="top">
                        <h3><?php echo htmlspecialchars($offre['title']); ?></h3>
                        <span class="badge"><?php echo htmlspecialchars($offre['contract_type']); ?></span>
                    </div>
                    <p class="entreprise">
                        <?php echo htmlspecialchars($offre['company_name']); ?> - <?php echo htmlspecialchars($offre['city']); ?>
                    </p>
                    <p class="desc">
                        <?php echo htmlspecialchars($offre['description']); ?>
                    </p>
                    <div class="bas-carte">
                        <span class="salaire"><?php echo htmlspecialchars($offre['salary']); ?></span>
                        <a href="offre.php?id=<?php echo $offre['id']; ?>" class="bouton-postuler">Voir l'offre</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <footer style="text-align: center; padding: 20px; font-size: 12px; color: #aaa;">
        <p>&copy; 2024 StageHunter - Matis et Nathan </p>
    </footer>

</body>
</html>