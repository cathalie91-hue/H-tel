<?php
session_start();
require_once '../../config/database.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'CLIENT') {
    header('Location: ../login.php'); exit;
}

$db   = Database::getInstance()->getConnection();
$user = $_SESSION['user'];
$id   = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: index.php'); exit;
}

$stmt = $db->prepare("
    SELECT r.*, ch.numero, ch.prix, cat.nom AS categorie,
           DATEDIFF(r.date_depart, r.date_arrivee) AS nb_nuits
    FROM reservations r
    JOIN chambres ch ON r.id_chambre = ch.id_chambre
    LEFT JOIN categories cat ON ch.id_categorie = cat.id_categorie
    WHERE r.id_reservation = ? AND r.id_client = ?
");
$stmt->execute([$id, $user['id_user']]);
$reservation = $stmt->fetch();

if (!$reservation) {
    header('Location: index.php'); exit;
}

$total = $reservation['nb_nuits'] * $reservation['prix'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Confirmation — Réservation</title>

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
    <a href="index.php"><i class="fas fa-calendar-alt"></i> Réservations</a>
    <a href="../../controllers/AuthController.php?action=logout" class="btn btn-outline btn-sm">Déconnexion</a>
  </nav>
</header>

<main style="padding:60px;display:flex;justify-content:center;">
  <div style="max-width:560px;width:100%;">

    <!-- Success Icon -->
    <div style="text-align:center;margin-bottom:32px;">
      <div style="width:80px;height:80px;border-radius:50%;background:#dcfce7;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:36px;color:#166534;">
        <i class="fas fa-check-circle"></i>
      </div>
      <h1 style="font-size:28px;font-weight:800;margin-bottom:8px;">Réservation soumise !</h1>
      <p class="text-muted">Votre demande sera confirmée par notre équipe sous peu.</p>
    </div>

    <!-- Récapitulatif -->
    <div class="form-card">
      <h3 class="fw-700 mb-3"><i class="fas fa-receipt" style="color:var(--primary);margin-right:8px;"></i> Récapitulatif</h3>

      <div style="display:flex;flex-direction:column;gap:14px;">

        <div style="display:flex;justify-content:space-between;padding:12px;background:var(--body-bg);border-radius:10px;">
          <span class="text-muted"><i class="fas fa-hashtag" style="margin-right:6px;"></i> Réservation</span>
          <span class="fw-700">#<?= $reservation['id_reservation'] ?></span>
        </div>

        <div style="display:flex;justify-content:space-between;padding:12px;background:var(--body-bg);border-radius:10px;">
          <span class="text-muted"><i class="fas fa-bed" style="margin-right:6px;"></i> Chambre</span>
          <span class="fw-700">N°<?= htmlspecialchars($reservation['numero']) ?> — <?= htmlspecialchars($reservation['categorie'] ?? 'Standard') ?></span>
        </div>

        <div style="display:flex;justify-content:space-between;padding:12px;background:var(--body-bg);border-radius:10px;">
          <span class="text-muted"><i class="fas fa-calendar-alt" style="margin-right:6px;"></i> Arrivée</span>
          <span class="fw-700"><?= date('d/m/Y', strtotime($reservation['date_arrivee'])) ?></span>
        </div>

        <div style="display:flex;justify-content:space-between;padding:12px;background:var(--body-bg);border-radius:10px;">
          <span class="text-muted"><i class="fas fa-calendar-alt" style="margin-right:6px;"></i> Départ</span>
          <span class="fw-700"><?= date('d/m/Y', strtotime($reservation['date_depart'])) ?></span>
        </div>

        <div style="display:flex;justify-content:space-between;padding:12px;background:var(--body-bg);border-radius:10px;">
          <span class="text-muted"><i class="fas fa-moon" style="margin-right:6px;"></i> Durée</span>
          <span class="fw-700"><?= $reservation['nb_nuits'] ?> nuit<?= $reservation['nb_nuits'] > 1 ? 's' : '' ?></span>
        </div>

        <div style="display:flex;justify-content:space-between;padding:12px;background:var(--body-bg);border-radius:10px;">
          <span class="text-muted"><i class="fas fa-tag" style="margin-right:6px;"></i> Prix / nuit</span>
          <span class="fw-700"><?= number_format($reservation['prix'], 0, ',', ' ') ?> Ar</span>
        </div>

        <div style="display:flex;justify-content:space-between;padding:16px;background:var(--primary-light);border-radius:10px;margin-top:4px;">
          <span style="font-weight:700;font-size:16px;">Total estimé</span>
          <span style="font-weight:800;font-size:20px;color:var(--primary-dark);"><?= number_format($total, 0, ',', ' ') ?> Ar</span>
        </div>

      </div>

      <div style="margin-top:20px;">
        <span class="badge badge-warning" style="font-size:12px;padding:6px 16px;">
          <i class="fas fa-clock"></i> EN ATTENTE DE CONFIRMATION
        </span>
      </div>
    </div>

    <!-- Actions -->
    <div class="d-flex gap-2 mt-3" style="justify-content:center;">
      <a href="index.php" class="btn btn-primary"><i class="fas fa-calendar-alt"></i> Mes réservations</a>
      <a href="../index.php" class="btn btn-outline"><i class="fas fa-home"></i> Accueil</a>
    </div>

  </div>
</main>
</body>
</html>
