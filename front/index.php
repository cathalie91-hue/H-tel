<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'CLIENT') {
    header('Location: login.php'); exit;
}

$db = Database::getInstance()->getConnection();
$user = $_SESSION['user'];

// Chambres disponibles
$chambres = $db->query("
    SELECT c.*, cat.nom AS categorie
    FROM chambres c
    LEFT JOIN categories cat ON c.id_categorie = cat.id_categorie
    WHERE c.statut = 'DISPONIBLE'
    LIMIT 6
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hôtel — Accueil</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<!-- HEADER -->
<header class="front-header">
  <div class="front-logo"><i class="fas fa-hotel"></i> Hôtel<span>Luxe</span></div>
  <nav class="front-nav">
    <a href="index.php"><i class="fas fa-home"></i> Accueil</a>
    <a href="chambres/index.php"><i class="fas fa-bed"></i> Chambres</a>
    <a href="menu/index.php"><i class="fas fa-utensils"></i> Restaurant</a>
    <a href="reservation/index.php"><i class="fas fa-calendar-alt"></i> Mes réservations</a>
    <a href="profil/index.php"><i class="fas fa-user"></i> Profil</a>
    <a href="../controllers/AuthController.php?action=logout" class="btn btn-outline btn-sm">Déconnexion</a>
  </nav>
</header>

<!-- HERO -->
<section class="hero">
  <div class="hero-content">
    <h1>Bienvenue, <span><?= htmlspecialchars($user['prenom']) ?></span></h1>
    <p>Trouvez la chambre parfaite pour votre séjour inoubliable</p>

    <form class="hero-search" action="chambres/index.php" method="GET">
      <input type="date" name="arrivee" placeholder="Date d'arrivée" min="<?= date('Y-m-d') ?>">
      <input type="date" name="depart"  placeholder="Date de départ">
      <select name="categorie" class="form-select">
        <option value="">Toutes catégories</option>
        <?php
        $cats = $db->query("SELECT * FROM categories")->fetchAll();
        foreach ($cats as $c):
        ?>
        <option value="<?= $c['id_categorie'] ?>"><?= htmlspecialchars($c['nom']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Rechercher</button>
    </form>
  </div>
</section>

<!-- CHAMBRES EN VEDETTE -->
<section style="padding:60px 60px;">
  <div class="d-flex justify-between align-center mb-3">
    <div>
      <h2 style="font-size:24px;font-weight:800;"><i class="fas fa-bed" style="color:var(--primary);margin-right:8px;"></i> Chambres disponibles</h2>
      <p class="text-muted fs-sm">Choisissez parmi nos meilleures options</p>
    </div>
    <a href="chambres/index.php" class="btn btn-outline">Voir toutes <i class="fas fa-arrow-right"></i></a>
  </div>

  <div class="chambres-grid">
    <?php foreach ($chambres as $ch): ?>
    <div class="chambre-card" onclick="location.href='reservation/index.php?chambre=<?= $ch['id_chambre'] ?>'">
      <div class="card-img">
        <?php if ($ch['image'] && file_exists('../uploads/chambres/' . $ch['image'])): ?>
          <img src="../uploads/chambres/<?= htmlspecialchars($ch['image']) ?>" alt="Chambre" style="width:100%;height:100%;object-fit:cover;">
        <?php else: ?>
          <div style="width:100%;height:100%;background-color:#f0f0f0;display:flex;align-items:center;justify-content:center;">
            <i class="fas fa-image" style="font-size:48px;color:#ccc;"></i>
          </div>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <p class="card-cat"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($ch['categorie'] ?? 'Standard') ?> — Chambre N°<?= $ch['numero'] ?></p>
        <h3 class="card-title"><?= htmlspecialchars($ch['categorie'] ?? 'Chambre') ?> Deluxe</h3>
        <p class="fs-sm text-muted mb-2"><?= htmlspecialchars($ch['description'] ?? 'Chambre confortable et bien équipée') ?></p>
        <div class="d-flex justify-between align-center mt-2">
          <div class="card-price"><?= number_format($ch['prix'], 0, ',', ' ') ?> Ar <span>/ nuit</span></div>
          <span class="badge badge-success"><i class="fas fa-check-circle"></i> Disponible</span>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- SECTION RESTAURANT -->
<section style="padding:0 60px 60px;">
  <div style="background:linear-gradient(135deg,#0F172A,#1e3a2f);border-radius:24px;padding:48px;display:flex;align-items:center;justify-content:space-between;gap:32px;">
    <div>
      <h2 style="color:#fff;font-size:28px;font-weight:800;margin-bottom:12px;"><i class="fas fa-utensils" style="margin-right:10px;"></i> Notre Restaurant</h2>
      <p style="color:rgba(255,255,255,0.7);font-size:16px;margin-bottom:24px;">
        Savourez nos plats traditionnels malagasy et internationale directement en chambre.
      </p>
      <a href="menu/index.php" class="btn btn-primary btn-lg">Voir le menu</a>
    </div>
    <div style="font-size:80px;opacity:0.3;color:var(--primary);">
      <i class="fas fa-concierge-bell"></i>
    </div>
  </div>
</section>

</body>
</html>