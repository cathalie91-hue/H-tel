<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['ADMIN', 'EMPLOYE'])) {
    header('Location: ../front/login.php'); exit;
}

$db   = Database::getInstance()->getConnection();
$user = $_SESSION['user'];

// Statistiques
$nb_clients      = $db->query("SELECT COUNT(*) FROM users WHERE role='CLIENT'")->fetchColumn();
$nb_chambres     = $db->query("SELECT COUNT(*) FROM chambres")->fetchColumn();
$nb_reservations = $db->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
$nb_disponibles  = $db->query("SELECT COUNT(*) FROM chambres WHERE statut='DISPONIBLE'")->fetchColumn();
$nb_commandes    = $db->query("SELECT COUNT(*) FROM commandes WHERE statut='EN_COURS'")->fetchColumn();

// Dernières réservations
$reservations = $db->query("
    SELECT r.*, u.nom, u.prenom, ch.numero
    FROM reservations r
    JOIN users u ON r.id_client = u.id_user
    JOIN chambres ch ON r.id_chambre = ch.id_chambre
    ORDER BY r.created_at DESC LIMIT 8
")->fetchAll();

// Stats par mois (graphique)
$monthly = $db->query("
    SELECT DATE_FORMAT(created_at,'%b') AS mois, COUNT(*) AS total
    FROM reservations
    WHERE YEAR(created_at) = YEAR(CURDATE())
    GROUP BY MONTH(created_at)
    ORDER BY MONTH(created_at)
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — Back Office</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="back-layout">
  <?php include 'partials/sidebar.php'; ?>

  <div class="main-content">
    <!-- TOPBAR -->
    <div class="topbar">
      <div class="topbar-title">
        <h1><i class="fas fa-chart-pie"></i> Tableau de bord</h1>
        <p>Bienvenue, <?= htmlspecialchars($user['prenom']) ?> · <?= date('d/m/Y') ?></p>
      </div>
      <div class="topbar-actions">
        <div class="topbar-search">
          <i class="fas fa-search" style="color:var(--text-muted);"></i> <input type="text" placeholder="Rechercher...">
        </div>
        <button class="notif-btn"><i class="fas fa-bell"></i><span class="notif-dot"></span></button>
      </div>
    </div>

    <div class="page-content">

      <!-- STATS CARDS -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon green"><i class="fas fa-users"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?= $nb_clients ?></div>
            <div class="stat-label">Clients inscrits</div>
            <div class="stat-change"><i class="fas fa-arrow-up"></i> Total</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon blue"><i class="fas fa-bed"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?= $nb_chambres ?></div>
            <div class="stat-label">Chambres total</div>
            <div class="stat-change"><i class="fas fa-check-circle"></i> <?= $nb_disponibles ?> disponibles</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon orange"><i class="fas fa-calendar-alt"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?= $nb_reservations ?></div>
            <div class="stat-label">Réservations</div>
            <div class="stat-change"><i class="fas fa-arrow-up"></i> Toutes périodes</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon purple"><i class="fas fa-utensils"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?= $nb_commandes ?></div>
            <div class="stat-label">Commandes en cours</div>
            <div class="stat-change"><i class="fas fa-clock"></i> En attente</div>
          </div>
        </div>
      </div>

      <!-- GRAPHIQUE + TABLEAU -->
      <div style="display:grid;grid-template-columns:1fr 1.6fr;gap:24px;margin-bottom:28px;">

        <!-- Graphique -->
        <div class="table-card" style="padding:24px;">
          <h3 class="fw-700 mb-3"><i class="fas fa-chart-bar" style="color:var(--primary);margin-right:8px;"></i> Réservations <?= date('Y') ?></h3>
          <canvas id="chartRes" height="200"></canvas>
        </div>

        <!-- Dernières réservations -->
        <div class="table-card">
          <div class="table-header">
            <h3><i class="fas fa-clock"></i> Dernières réservations</h3>
            <a href="reservations/index.php" class="btn btn-outline btn-sm">Voir tout</a>
          </div>
          <div class="table-responsive">
            <table>
              <thead>
                <tr>
                  <th>Client</th>
                  <th>Chambre</th>
                  <th>Arrivée</th>
                  <th>Statut</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($reservations as $r): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($r['prenom'] . ' ' . $r['nom']) ?></strong></td>
                  <td>N°<?= htmlspecialchars($r['numero']) ?></td>
                  <td><?= date('d/m/Y', strtotime($r['date_arrivee'])) ?></td>
                  <td>
                    <?php
                    $badges = [
                      'EN_ATTENTE'  => 'warning',
                      'CONFIRMEE'   => 'success',
                      'ANNULEE'     => 'danger',
                      'TERMINEE'    => 'muted',
                    ];
                    $icons = ['EN_ATTENTE'=>'fa-clock','CONFIRMEE'=>'fa-check-circle','ANNULEE'=>'fa-times-circle','TERMINEE'=>'fa-flag-checkered'];
                    $s = $r['statut'];
                    ?>
                    <span class="badge badge-<?= $badges[$s] ?? 'muted' ?>">
                      <i class="fas <?= $icons[$s] ?? '' ?>"></i> <?= $s ?>
                    </span>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div><!-- /page-content -->
  </div><!-- /main-content -->
</div>

<script src="../libs/chart.min.js"></script>
<script>
const ctx = document.getElementById('chartRes');
const labels = <?= json_encode(array_column($monthly, 'mois')) ?>;
const data   = <?= json_encode(array_column($monthly, 'total')) ?>;

new Chart(ctx, {
  type: 'bar',
  data: {
    labels,
    datasets: [{
      label: 'Réservations',
      data,
      backgroundColor: 'rgba(46,204,154,0.2)',
      borderColor: '#2ECC9A',
      borderWidth: 2,
      borderRadius: 8,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      y: { beginAtZero: true, grid: { color: '#f0fdf8' } },
      x: { grid: { display: false } }
    }
  }
});
</script>
</body>
</html>