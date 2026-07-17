<?php
session_start();
require_once '../../config/database.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'CLIENT') {
    header('Location: ../login.php'); exit;
}

$db   = Database::getInstance()->getConnection();
$user = $_SESSION['user'];
$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom       = trim($_POST['nom'] ?? '');
    $prenom    = trim($_POST['prenom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm'] ?? '';

    if (!$nom || !$prenom) {
        $error = 'Nom et prénom sont requis.';
    } elseif ($password && $password !== $confirm) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif ($password && strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } else {
        if ($password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $db->prepare("UPDATE users SET nom=?, prenom=?, telephone=?, password=? WHERE id_user=?")
               ->execute([$nom, $prenom, $telephone, $hash, $user['id_user']]);
        } else {
            $db->prepare("UPDATE users SET nom=?, prenom=?, telephone=? WHERE id_user=?")
               ->execute([$nom, $prenom, $telephone, $user['id_user']]);
        }
        // Rafraîchir session
        $stmt = $db->prepare("SELECT * FROM users WHERE id_user=?");
        $stmt->execute([$user['id_user']]);
        $_SESSION['user'] = $stmt->fetch();
        $user = $_SESSION['user'];
        $success = 'Profil mis à jour avec succès !';
    }
}

// Stats client
$nb_res = $db->prepare("SELECT COUNT(*) FROM reservations WHERE id_client=?");
$nb_res->execute([$user['id_user']]);
$nb_reservations = $nb_res->fetchColumn();

$nb_cmd = $db->prepare("SELECT COUNT(*) FROM commandes WHERE id_client=?");
$nb_cmd->execute([$user['id_user']]);
$nb_commandes = $nb_cmd->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Mon Profil</title>

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-EJ8BYRQw0H"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-EJ8BYRQw0H');
</script>
  
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<header class="front-header">
  <div class="front-logo"><i class="fas fa-hotel"></i> Hôtel<span>Luxe</span></div>
  <nav class="front-nav">
    <a href="../index.php"><i class="fas fa-home"></i> Accueil</a>
    <a href="../chambres/index.php"><i class="fas fa-bed"></i> Chambres</a>
    <a href="../menu/index.php"><i class="fas fa-utensils"></i> Restaurant</a>
    <a href="../reservation/index.php"><i class="fas fa-calendar-alt"></i> Réservations</a>
    <a href="index.php" style="color:var(--primary-dark);font-weight:700;"><i class="fas fa-user"></i> Profil</a>
    <a href="../../controllers/AuthController.php?action=logout" class="btn btn-outline btn-sm">Déconnexion</a>
  </nav>
</header>

<main style="padding:40px 60px;max-width:1000px;margin:0 auto;">

  <h1 style="font-size:28px;font-weight:800;margin-bottom:24px;"><i class="fas fa-user" style="color:var(--primary);margin-right:8px;"></i> Mon Profil</h1>

  <!-- STATS RAPIDES -->
  <div class="stats-grid" style="margin-bottom:32px;">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="fas fa-calendar-alt"></i></div>
      <div class="stat-info">
        <div class="stat-value"><?= $nb_reservations ?></div>
        <div class="stat-label">Réservations totales</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon orange"><i class="fas fa-utensils"></i></div>
      <div class="stat-info">
        <div class="stat-value"><?= $nb_commandes ?></div>
        <div class="stat-label">Commandes passées</div>
      </div>
    </div>
  </div>

  <?php if ($error): ?>
  <div class="toast error" style="position:static;margin-bottom:16px;animation:none;"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
  <div class="toast success" style="position:static;margin-bottom:16px;animation:none;"><i class="fas fa-check-circle"></i> <?= $success ?></div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:auto 1fr;gap:32px;align-items:start;">

    <!-- Avatar -->
    <div style="text-align:center;">
      <div style="width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary-dark));display:flex;align-items:center;justify-content:center;font-size:40px;font-weight:800;color:#fff;margin:0 auto 12px;">
        <?= strtoupper(substr($user['prenom'],0,1)) ?>
      </div>
      <div style="font-weight:700;font-size:16px;"><?= htmlspecialchars($user['prenom'].' '.$user['nom']) ?></div>
      <div><span class="badge badge-success" style="margin-top:4px;">CLIENT</span></div>
      <div class="text-muted fs-sm mt-1">Membre depuis <?= date('d/m/Y', strtotime($user['created_at'])) ?></div>
    </div>

    <!-- Formulaire -->
    <div class="form-card">
      <h3 class="fw-700 mb-3"><i class="fas fa-edit" style="color:var(--primary);margin-right:8px;"></i> Modifier mes informations</h3>
      <form method="POST">
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Nom *</label>
            <input type="text" name="nom" class="form-control" required value="<?= htmlspecialchars($_POST['nom'] ?? $user['nom']) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Prénom *</label>
            <input type="text" name="prenom" class="form-control" required value="<?= htmlspecialchars($_POST['prenom'] ?? $user['prenom']) ?>">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Email <span class="text-muted fs-sm">(non modifiable)</span></label>
          <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled style="opacity:0.6;">
        </div>

        <div class="form-group">
          <label class="form-label">Téléphone</label>
          <input type="text" name="telephone" class="form-control" placeholder="+261 34 000 0000" value="<?= htmlspecialchars($_POST['telephone'] ?? $user['telephone']) ?>">
        </div>

        <hr style="border:none;border-top:1px solid var(--border);margin:20px 0;">
        <p class="fw-700 fs-sm mb-2"><i class="fas fa-lock" style="color:var(--primary);margin-right:6px;"></i> Changer le mot de passe <span class="text-muted">(laisser vide = inchangé)</span></p>

        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Nouveau mot de passe</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••">
          </div>
          <div class="form-group">
            <label class="form-label">Confirmer</label>
            <input type="password" name="confirm" class="form-control" placeholder="••••••••">
          </div>
        </div>

        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer les modifications</button>
      </form>
    </div>
  </div>
</main>
</body>
</html>