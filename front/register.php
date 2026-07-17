<?php
session_start();
require_once '../config/database.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom       = trim($_POST['nom'] ?? '');
    $prenom    = trim($_POST['prenom'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm'] ?? '';

    if (!$nom || !$prenom || !$email || !$password) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } elseif ($password !== $confirm) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } else {
        $db   = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id_user FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Cet email est déjà utilisé.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (nom, prenom, email, password, role, telephone) VALUES (?,?,?,?,'CLIENT',?)");
            $stmt->execute([$nom, $prenom, $email, $hash, $telephone]);
            $success = 'Compte créé avec succès !';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inscription — Hôtel</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-card" style="max-width:520px;">

    <div class="auth-logo">
      <div class="icon"><i class="fas fa-star"></i></div>
      <h2>Créer un compte</h2>
      <p>Rejoignez-nous pour réserver votre séjour</p>
    </div>

    <?php if ($error): ?>
    <div class="toast error" style="position:static;margin-bottom:16px;animation:none;"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="toast success" style="position:static;margin-bottom:16px;animation:none;">
      <i class="fas fa-check-circle"></i> <?= $success ?> <a href="login.php" style="color:var(--primary-dark);">Se connecter</a>
    </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Nom *</label>
          <input type="text" name="nom" class="form-control" placeholder="FANOMEZANTSOA" required value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Prénom *</label>
          <input type="text" name="prenom" class="form-control" placeholder="Cathalie" required value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Email *</label>
        <input type="email" name="email" class="form-control" placeholder="cathalie@gmail.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label class="form-label">Téléphone</label>
        <input type="text" name="telephone" class="form-control" placeholder="+261 34 00 000 00" value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>">
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Mot de passe *</label>
          <input type="password" name="password" class="form-control" placeholder="••••••" required>
        </div>
        <div class="form-group">
          <label class="form-label">Confirmer *</label>
          <input type="password" name="confirm" class="form-control" placeholder="••••••" required>
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-100 btn-lg mt-2">
        <i class="fas fa-rocket"></i> Créer mon compte
      </button>
    </form>

    <p class="text-center mt-3 fs-sm text-muted">
      Déjà un compte ? <a href="login.php" style="color:var(--primary-dark);font-weight:600;">Se connecter</a>
    </p>
  </div>
</div>
</body>
</html>