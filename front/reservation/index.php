<?php
session_start();
require_once '../../config/database.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'CLIENT') {
    header('Location: ../login.php'); exit;
}

$db   = Database::getInstance()->getConnection();
$user = $_SESSION['user'];
$error = ''; $success = '';

// Nouvelle réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserver'])) {
    $id_chambre  = (int)$_POST['id_chambre'];
    $date_arr    = $_POST['date_arrivee'];
    $date_dep    = $_POST['date_depart'];

    if (!$id_chambre || !$date_arr || !$date_dep) {
        $error = 'Veuillez remplir tous les champs.';
    } elseif ($date_dep <= $date_arr) {
        $error = 'La date de départ doit être après la date d\'arrivée.';
    } else {
        // Vérifier disponibilité
        $check = $db->prepare("SELECT id_reservation FROM reservations WHERE id_chambre=? AND statut IN ('EN_ATTENTE','CONFIRMEE') AND NOT (date_depart<=? OR date_arrivee>=?)");
        $check->execute([$id_chambre, $date_arr, $date_dep]);
        if ($check->fetch()) {
            $error = 'Cette chambre est déjà réservée pour ces dates.';
        } else {
            $db->prepare("INSERT INTO reservations (id_client,id_chambre,date_arrivee,date_depart,statut) VALUES (?,?,?,?,'EN_ATTENTE')")
               ->execute([$user['id_user'], $id_chambre, $date_arr, $date_dep]);
            
            $last_id = $db->lastInsertId();
            header('Location: confirmation.php?id=' . $last_id);
            exit;
        }
    }
}

// Annuler
if (isset($_GET['annuler'])) {
    $id = (int)$_GET['annuler'];
    $db->prepare("UPDATE reservations SET statut='ANNULEE' WHERE id_reservation=? AND id_client=? AND statut='EN_ATTENTE'")
       ->execute([$id, $user['id_user']]);
    $success = 'Réservation annulée.';
}

// Chambres disponibles
$chambres = $db->query("SELECT c.*, cat.nom AS categorie FROM chambres c LEFT JOIN categories cat ON c.id_categorie=cat.id_categorie WHERE c.statut='DISPONIBLE'")->fetchAll();

// Mes réservations
$mes_reservations = $db->prepare("
    SELECT r.*, ch.numero, ch.prix, cat.nom AS categorie,
           DATEDIFF(r.date_depart, r.date_arrivee) AS nb_nuits
    FROM reservations r
    JOIN chambres ch ON r.id_chambre=ch.id_chambre
    LEFT JOIN categories cat ON ch.id_categorie=cat.id_categorie
    WHERE r.id_client=?
    ORDER BY r.created_at DESC
");
$mes_reservations->execute([$user['id_user']]);
$mes_res = $mes_reservations->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Mes réservations</title>
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
    <a href="index.php" style="color:var(--primary-dark);font-weight:700;"><i class="fas fa-calendar-alt"></i> Réservations</a>
    <a href="../../controllers/AuthController.php?action=logout" class="btn btn-outline btn-sm">Déconnexion</a>
  </nav>
</header>

<main style="padding:40px 60px;">
  <h1 style="font-size:28px;font-weight:800;margin-bottom:8px;"><i class="fas fa-calendar-alt" style="color:var(--primary);margin-right:8px;"></i> Mes Réservations</h1>
  <p class="text-muted mb-3">Gérez vos séjours à l'hôtel</p>

  <?php if ($error): ?>
  <div class="toast error" style="position:static;margin-bottom:16px;animation:none;"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
  <div class="toast success" style="position:static;margin-bottom:16px;animation:none;"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 1.5fr;gap:28px;">

    <!-- FORMULAIRE RÉSERVATION -->
    <div>
      <div class="form-card">
        <h3 class="fw-700 mb-3"><i class="fas fa-plus-circle" style="color:var(--primary);margin-right:8px;"></i> Nouvelle réservation</h3>
        <form method="POST">
          <div class="form-group">
            <label class="form-label">Chambre</label>
            <select name="id_chambre" class="form-control form-select" required>
              <option value="">— Choisir une chambre —</option>
              <?php foreach ($chambres as $ch): ?>
              <option value="<?= $ch['id_chambre'] ?>">
                N°<?= $ch['numero'] ?> — <?= htmlspecialchars($ch['categorie'] ?? 'Standard') ?> — <?= number_format($ch['prix'],0,',',' ') ?> Ar/nuit
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Date d'arrivée</label>
            <input type="date" name="date_arrivee" class="form-control" required min="<?= date('Y-m-d') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Date de départ</label>
            <input type="date" name="date_depart" class="form-control" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
          </div>
          <button type="submit" name="reserver" class="btn btn-primary w-100"><i class="fas fa-calendar-alt"></i> Réserver</button>
        </form>
      </div>
    </div>

    <!-- MES RÉSERVATIONS -->
    <div>
      <?php foreach ($mes_res as $r): ?>
      <?php
      $nb = $r['nb_nuits'];
      $total = $nb * $r['prix'];
      $b = ['EN_ATTENTE'=>'warning','CONFIRMEE'=>'success','ANNULEE'=>'danger','TERMINEE'=>'muted'];
      $i = ['EN_ATTENTE'=>'fa-clock','CONFIRMEE'=>'fa-check-circle','ANNULEE'=>'fa-times-circle','TERMINEE'=>'fa-flag-checkered'];
      $s = $r['statut'];
      ?>
      <div style="background:#fff;border-radius:var(--radius);border:1px solid var(--border);padding:20px;margin-bottom:16px;">
        <div class="d-flex justify-between align-center mb-2">
          <div>
            <span class="fw-700" style="font-size:16px;"><i class="fas fa-bed" style="color:var(--primary);margin-right:6px;"></i> Chambre N°<?= $r['numero'] ?></span>
            <span class="text-muted fs-sm ml-2"> — <?= htmlspecialchars($r['categorie'] ?? '') ?></span>
          </div>
          <span class="badge badge-<?= $b[$s] ?>"><i class="fas <?= $i[$s] ?>"></i> <?= $s ?></span>
        </div>
        <div class="d-flex gap-2 text-muted fs-sm mb-2">
          <span><i class="fas fa-calendar-alt" style="margin-right:4px;"></i> Arrivée : <strong><?= date('d/m/Y',strtotime($r['date_arrivee'])) ?></strong></span>
          <span><i class="fas fa-arrow-right"></i></span>
          <span><i class="fas fa-calendar-alt" style="margin-right:4px;"></i> Départ : <strong><?= date('d/m/Y',strtotime($r['date_depart'])) ?></strong></span>
        </div>
        <div class="d-flex justify-between align-center">
          <span class="fw-700 text-primary"><?= number_format($total,0,',',' ') ?> Ar (<?= $nb ?> nuit<?= $nb>1?'s':'' ?>)</span>
          <?php if ($s === 'EN_ATTENTE'): ?>
          <a href="?annuler=<?= $r['id_reservation'] ?>"
             onclick="return confirm('Annuler cette réservation ?')"
             class="btn btn-danger btn-sm"><i class="fas fa-times-circle"></i> Annuler</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($mes_res)): ?>
      <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-calendar-alt"></i></div>
        <h3>Aucune réservation pour l'instant</h3>
        <p class="text-muted">Réservez votre première chambre !</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</main>
</body>
</html>