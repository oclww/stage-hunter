<?php
session_start();
require_once 'config/db.php';

if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$message = "";
$val_email = "";
$val_role = "candidat";
$val_company = "";
$val_siret = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $val_email = htmlspecialchars($_POST['email']);
    $val_role = $_POST['role'];
    $val_company = isset($_POST['company_name']) ? htmlspecialchars($_POST['company_name']) : "";
    $val_siret = isset($_POST['siret']) ? htmlspecialchars($_POST['siret']) : "";
    $password_clair = $_POST['password'];

    // --- 1. VÉRIFICATION FORMAT EMAIL (LOCAL) ---
    // Bloque "t@t" ou "a@a" immédiatement
    if (!filter_var($val_email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[\w\-\.]+@([\w\-]+\.)+[\w\-]{2,4}$/', $val_email)) {
        $message = "❌ Format d'email invalide ";
    }
    // --- 2. SÉCURITÉ MOT DE PASSE ---
    elseif (!preg_match('/[A-Z]/', $password_clair) || !preg_match('/[\W]/', $password_clair)) {
        $message = "❌ Le mot de passe doit contenir <strong>1 Majuscule</strong> et <strong>1 Caractère spécial</strong>.";
    } 
    // --- 3. VÉRIFICATION SIRET (ENTREPRISE) ---
    elseif ($val_role == 'entreprise' && !preg_match('/^[0-9]{14}$/', $val_siret)) {
        $message = "❌ Erreur Entreprise : Le SIRET doit faire 14 chiffres.";
    }
    else {
        $siret_valide = true;
        $nom_officiel_entreprise = "";
        $raison_refus = "";

        // API GOUV SIRET
        if ($val_role == 'entreprise') {
            $url_gouv = "https://recherche-entreprises.api.gouv.fr/search?q=$val_siret";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url_gouv);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $resp_gouv = curl_exec($ch);
            curl_close($ch);
            $data_gouv = json_decode($resp_gouv, true);

            if (empty($data_gouv['results'])) {
                $siret_valide = false;
                $raison_refus = "Ce numéro SIRET n'existe pas.";
            } else {
                $nom_officiel_entreprise = $data_gouv['results'][0]['nom_complet'];
            }
        }

        if (!$siret_valide) {
            $message = "❌ Erreur Entreprise : " . $raison_refus;
        } else {
            // --- 4. SÉCURITÉ EMAIL AVANCÉE (HUNTER.IO) ---
            $password_hash = password_hash($password_clair, PASSWORD_DEFAULT);
            
            // 👇 TA CLÉ API HUNTER ICI
            $api_key = "dacc718c28b91ada2a43d75e578c0bf57c63e652"; 

            $url = "https://api.hunter.io/v2/email-verifier?email=$val_email&api_key=$api_key";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
            $response = curl_exec($ch);
            curl_close($ch);

            $email_valide = true; 
            
            if ($response) {
                $data = json_decode($response, true);
                
                if (isset($data['data'])) {
                    // A. Vérifier si l'email existe (undeliverable)
                    if ($data['data']['result'] == "undeliverable") {
                        $email_valide = false;
                        $raison_refus = "Cette adresse email n'existe pas (vérifié par Hunter).";
                    }
                    // B. Vérifier Email Jetable
                    if ($data['data']['disposable'] == true) {
                        $email_valide = false;
                        $raison_refus = "Emails jetables interdits.";
                    }
                    // C. Vérifier Webmail pour Entreprise (Gmail/Yahoo interdit)
                    if ($val_role == 'entreprise' && $data['data']['webmail'] == true) {
                        $email_valide = false;
                        $raison_refus = "Les entreprises doivent utiliser un email pro (pas Gmail/Yahoo).";
                    }
                }
            }

            if (!$email_valide) {
                $message = "❌ Erreur Email : " . $raison_refus;
            } else {
                // --- 5. INSCRIPTION BDD ---
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$val_email]);
                
                if ($stmt->rowCount() > 0) {
                    $message = "❌ Cet email est déjà utilisé.";
                } else {
                    $sql = "INSERT INTO users (email, password, role) VALUES (?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    
                    if ($stmt->execute([$val_email, $password_hash, $val_role])) {
                        $user_id = $pdo->lastInsertId();
                        if ($val_role == 'entreprise') {
                            $nom_final = !empty($nom_officiel_entreprise) ? $nom_officiel_entreprise : $val_company;
                            $sql_comp = "INSERT INTO companies (name, user_id, city, siret) VALUES (?, ?, 'France', ?)";
                            $stmt_comp = $pdo->prepare($sql_comp);
                            $stmt_comp->execute([$nom_final, $user_id, $val_siret]);
                        }
                        $message = "✅ Compte créé ! <a href='login.php'>Connexion</a>";
                    } else {
                        $message = "❌ Erreur technique.";
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription</title>
    <link rel="stylesheet" href="public/style.css">
    <style>
        /* Corrige l'alignement du mot de passe */
        .password-container {
            position: relative; /* Permet de placer l'oeil par rapport à ce bloc */
            width: 100%;
            margin-bottom: 15px;
        }

        .password-container input {
            width: 100%;
            padding: 10px;
            padding-right: 40px; 
            box-sizing: border-box; 
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .eye-icon {
            position: absolute;
            right: 10px;        
            top: 50%;           
            transform: translateY(-50%); 
            cursor: pointer;
            font-size: 1.2rem;
            user-select: none;  
        }
    </style>
</head>
<body style="display:flex; justify-content:center; align-items:center; height:100vh; background:#f4f6f8; font-family:sans-serif;">

    <div style="background:white; padding:40px; border-radius:10px; width:400px; box-shadow:0 4px 10px rgba(0,0,0,0.1);">
        <h2 style="text-align:center;">Créer un compte 🚀</h2>
        
        <?php if($message): 
            $is_success = strpos($message, '✅') !== false;
            $color = $is_success ? '#d1e7dd' : '#fee2e2';
            $text = $is_success ? '#0f5132' : '#b91c1c';
        ?>
            <p style="background:<?php echo $color; ?>; color:<?php echo $text; ?>; padding:10px; border-radius:5px; text-align:center; font-size:0.9em;">
                <?php echo $message; ?>
            </p>
        <?php endif; ?>

        <form method="POST">
            <label>Email</label><br>
            <input type="email" name="email" value="<?php echo $val_email; ?>" required style="width:100%; padding:10px; margin-bottom:15px;"><br>
            
            <label>Mot de passe</label>
            <span style="font-size:0.8em; color:#666;">(1 Majuscule + 1 Caractère spécial)</span>
            
            <div class="password-container">
                <input type="password" name="password" id="reg-pass" required>
                <span onclick="togglePassword('reg-pass', this)" class="eye-icon">👁️</span>
            </div>

            <label>Vous êtes ?</label><br>
            <select name="role" id="role-select" style="width:100%; padding:10px; margin-bottom:15px;" onchange="toggleCompany()">
                <option value="candidat" <?php if($val_role == 'candidat') echo 'selected'; ?>>👨‍🎓 Étudiant / Candidat</option>
                <option value="entreprise" <?php if($val_role == 'entreprise') echo 'selected'; ?>>🏢 Entreprise</option>
            </select>

            <div id="company-field" style="display:none; margin-bottom:15px; border-left: 3px solid #2563eb; padding-left: 10px;">
                <label>Nom de l'entreprise</label>
                <input type="text" name="company_name" value="<?php echo $val_company; ?>" placeholder="Ex: Google" style="width:96%; padding:8px; margin-bottom:10px;">
                <label>Numéro SIRET (14 chiffres)</label>
                <input type="text" name="siret" value="<?php echo $val_siret; ?>" maxlength="14" placeholder="Ex: 44306184100047" style="width:96%; padding:8px;">
            </div>

            <button type="submit" class="btn" style="width:100%; background:#2563eb; color:white; padding:10px; border:none; cursor:pointer; margin-top:15px;">S'inscrire 🚀</button>
        </form>
        
        <p style="text-align:center; margin-top:20px;">
            <a href="login.php" style="color:#666; text-decoration:none;">Déjà un compte ? Se connecter</a>
        </p>
    </div>

    <script>
        function toggleCompany() {
            var role = document.getElementById('role-select').value;
            var field = document.getElementById('company-field');
            if (role === 'entreprise') {
                field.style.display = 'block';
                document.getElementsByName('siret')[0].required = true;
            } else {
                field.style.display = 'none';
                document.getElementsByName('siret')[0].required = false;
            }
        }
        window.onload = toggleCompany;
        // FONCTION POUR AFFICHER/CACHER LE MOT DE PASSE
    function togglePassword(inputId, icon) {
        var input = document.getElementById(inputId);
        if (input.type === "password") {
            input.type = "text";  
            icon.innerText = "🙈";
        } else {
            input.type = "password"; 
            icon.innerText = "👁️"; 
        }
    }
    </script>
</body>
</html>
