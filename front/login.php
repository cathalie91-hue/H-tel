<?php
session_start();
require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $db  = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;
            if ($user['role'] === 'CLIENT') {
                header('Location: index.php');
            } else {
                header('Location: ../back/dashboard.php');
            }
            exit;
        } else {
            $error = 'Email ou mot de passe incorrect.';
        }
    } else {
        $error = 'Veuillez remplir tous les champs.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion — Hôtel</title>

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-EJ8BYRQw0H"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-EJ8BYRQw0H');
</script>
  
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-card">

    <div class="auth-logo">
      <div class="icon"><i class="fas fa-hotel"></i></div>
      <h2>Bienvenue</h2>
      <p>Connectez-vous à votre espace</p>
    </div>

    <?php if ($error): ?>
    <div class="toast error" style="position:static;margin-bottom:16px;animation:none;">
      <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label class="form-label">Adresse email</label>
        <input type="email" name="email" class="form-control"
               placeholder="votre@email.com" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label class="form-label">Mot de passe</label>
        <input type="password" name="password" class="form-control"
               placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn btn-primary w-100 btn-lg mt-2">
        <i class="fas fa-lock"></i> Se connecter
      </button>
    </form>

    <p class="text-center mt-3 fs-sm text-muted">
      Pas encore de compte ?
      <a href="register.php" style="color:var(--primary-dark);font-weight:600;">S'inscrire</a>
    </p>

    <!-- Accès rapide démo -->
    <!-- <div style="margin-top:24px;padding:16px;background:var(--primary-light);border-radius:12px;">
      <p class="fs-sm fw-700 text-primary mb-1"><i class="fas fa-key"></i> Accès démo :</p>
      <p class="fs-sm text-muted">Admin : admin@hotel.com / password</p>
      <p class="fs-sm text-muted">Employé : employe@hotel.com / password</p>
    </div> -->
  </div>
</div>
</body>
</html>