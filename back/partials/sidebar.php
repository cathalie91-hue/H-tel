<?php
$current = basename($_SERVER['PHP_SELF']);
$dir     = basename(dirname($_SERVER['PHP_SELF']));
$role    = $_SESSION['user']['role'] ?? '';
$initial = strtoupper(substr($_SESSION['user']['prenom'] ?? 'U', 0, 1));
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<aside class="sidebar" id="sidebar">

  <div class="sidebar-logo">
    <div class="logo-icon"><i class="fas fa-hotel"></i></div>
    <h2>HôtelLuxe</h2>
    <span>Système de gestion</span>
  </div>

  <div class="sidebar-user">
    <div class="avatar"><?= $initial ?></div>
    <div class="user-info">
      <div class="name"><?= htmlspecialchars($_SESSION['user']['prenom'] . ' ' . $_SESSION['user']['nom']) ?></div>
      <div class="role-badge"><?= $role ?></div>
    </div>
  </div>

  <nav class="sidebar-nav">

    <div class="nav-section">
      <div class="nav-section-title">Principal</div>
    </div>

    <a href="/hotel/back/dashboard.php" class="nav-link <?= ($current==='dashboard.php')?'active':'' ?>">
      <span class="nav-icon"><i class="fas fa-chart-pie"></i></span> Dashboard
    </a>

    <div class="nav-section">
      <div class="nav-section-title">Gestion</div>
    </div>

    <?php if ($role === 'ADMIN'): ?>
    <a href="/hotel/back/users/index.php" class="nav-link <?= ($dir==='users')?'active':'' ?>">
      <span class="nav-icon"><i class="fas fa-users"></i></span> Utilisateurs
    </a>
    <?php endif; ?>

    <a href="/hotel/back/chambres/index.php" class="nav-link <?= ($dir==='chambres')?'active':'' ?>">
      <span class="nav-icon"><i class="fas fa-bed"></i></span> Chambres
    </a>

    <a href="/hotel/back/reservations/index.php" class="nav-link <?= ($dir==='reservations')?'active':'' ?>">
      <span class="nav-icon"><i class="fas fa-calendar-alt"></i></span> Réservations
      <?php
      $db  = Database::getInstance()->getConnection();
      $nb  = $db->query("SELECT COUNT(*) FROM reservations WHERE statut='EN_ATTENTE'")->fetchColumn();
      if ($nb > 0): ?>
      <span class="nav-badge"><?= $nb ?></span>
      <?php endif; ?>
    </a>

    <div class="nav-section">
      <div class="nav-section-title">Restauration</div>
    </div>

    <a href="/hotel/back/menu/index.php" class="nav-link <?= ($dir==='menu')?'active':'' ?>">
      <span class="nav-icon"><i class="fas fa-utensils"></i></span> Menu
    </a>

    <a href="/hotel/back/commandes/index.php" class="nav-link <?= ($dir==='commandes')?'active':'' ?>">
      <span class="nav-icon"><i class="fas fa-shopping-cart"></i></span> Commandes
      <?php
      $nbC = $db->query("SELECT COUNT(*) FROM commandes WHERE statut='EN_COURS'")->fetchColumn();
      if ($nbC > 0): ?>
      <span class="nav-badge"><?= $nbC ?></span>
      <?php endif; ?>
    </a>

    <?php if ($role === 'ADMIN'): ?>
    <div class="nav-section">
      <div class="nav-section-title">Administration</div>
    </div>

    <a href="/hotel/back/stats/index.php" class="nav-link <?= ($dir==='stats')?'active':'' ?>">
      <span class="nav-icon"><i class="fas fa-chart-line"></i></span> Statistiques
    </a>
    <?php endif; ?>

  </nav>

  <div class="sidebar-footer">
    <a href="/hotel/controllers/AuthController.php?action=logout" class="btn-logout">
      <i class="fas fa-sign-out-alt"></i> Déconnexion
    </a>
  </div>
</aside>