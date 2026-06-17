<?php
session_start();
require_once '../../config/database.php';
if ($_SESSION['user']['role'] !== 'ADMIN') { header('Location: ../dashboard.php'); exit; }

$db = Database::getInstance()->getConnection();

$top_chambres = $db->query("
    SELECT ch.numero, COUNT(*) AS nb,
           SUM(DATEDIFF(r.date_depart,r.date_arrivee)*ch.prix) AS revenu
    FROM reservations r
    JOIN chambres ch ON r.id_chambre=ch.id_chambre
    WHERE r.statut IN ('CONFIRMEE','TERMINEE')
    GROUP BY r.id_chambre ORDER BY nb DESC LIMIT 5
")->fetchAll();

$top_plats = $db->query("
    SELECT m.nom, SUM(ci.quantite) AS total_vendu,
           SUM(ci.quantite*ci.prix) AS revenu
    FROM commande_items ci
    JOIN menu m ON ci.id_menu=m.id_menu
    GROUP BY ci.id_menu ORDER BY total_vendu DESC LIMIT 5
")->fetchAll();

// Stats réservations par mois
$res_mois = $db->query("
    SELECT DATE_FORMAT(created_at,'%b %Y') AS mois, COUNT(*) AS total
    FROM reservations
    WHERE statut IN ('CONFIRMEE','TERMINEE')
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY created_at DESC LIMIT 12
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Statistiques</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="back-layout">
        <?php include '../partials/sidebar.php'; ?>
        <div class="main-content">
            <div class="topbar">
                <div class="topbar-title">
                    <h1><i class="fas fa-chart-line"></i> Statistiques</h1>
                    <p>Vue d'ensemble des performances</p>
                </div>
            </div>
            <div class="page-content">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">

                    <!-- TOP CHAMBRES -->
                    <div class="table-card">
                        <div class="table-header">
                            <h3><i class="fas fa-bed"></i> Top Chambres</h3>
                        </div>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Chambre</th>
                                        <th>Réservations</th>
                                        <th>Revenus</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_chambres as $t): ?>
                                    <tr>
                                        <td>N°<?= $t['numero'] ?></td>
                                        <td><?= $t['nb'] ?></td>
                                        <td class="fw-700 text-primary"><?= number_format($t['revenu'],0,',',' ') ?> Ar</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TOP PLATS -->
                    <div class="table-card">
                        <div class="table-header">
                            <h3><i class="fas fa-utensils"></i> Top Menu</h3>
                        </div>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Article</th>
                                        <th>Qté vendue</th>
                                        <th>Revenus</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_plats as $t): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($t['nom']) ?></td>
                                        <td><?= $t['total_vendu'] ?></td>
                                        <td class="fw-700 text-primary"><?= number_format($t['revenu'],0,',',' ') ?> Ar</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- GRAPHIQUE RÉSERVATIONS -->
                <div class="table-card" style="padding:24px;">
                    <h3 class="fw-700 mb-3"><i class="fas fa-chart-bar" style="color:var(--primary);margin-right:8px;"></i> Réservations confirmées par mois</h3>
                    <canvas id="chartRes" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>
    <script src="../../libs/chart.min.js"></script>
    <script>
    new Chart(document.getElementById('chartRes'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_reverse(array_column($res_mois,'mois'))) ?>,
            datasets: [{
                label: 'Réservations',
                data: <?= json_encode(array_reverse(array_column($res_mois,'total'))) ?>,
                borderColor: '#2ECC9A',
                backgroundColor: 'rgba(46,204,154,0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#2ECC9A',
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