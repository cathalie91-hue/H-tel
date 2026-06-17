<?php
session_start();
require_once '../../config/database.php';
if (!isset($_SESSION['user'])||!in_array($_SESSION['user']['role'],['ADMIN','EMPLOYE'])) {
    header('Location: ../../front/login.php'); exit;
}

$db = Database::getInstance()->getConnection();

// Changer statut commande
if (isset($_GET['statut']) && isset($_GET['id'])) {
    $db->prepare("UPDATE commandes SET statut=? WHERE id_commande=?")
       ->execute([$_GET['statut'], (int)$_GET['id']]);
    $_SESSION['flash'] = ['type'=>'success','msg'=>'Statut mis à jour.'];
    header('Location: index.php'); exit;
}

$filtre = $_GET['filtre'] ?? '';
$where  = $filtre ? "WHERE c.statut = '" . addslashes($filtre) . "'" : '';

$commandes = $db->query("
    SELECT c.*,
           u.nom, u.prenom,
           r.date_arrivee, r.date_depart,
           ch.numero,
           COUNT(ci.id_item) AS nb_articles,
           SUM(ci.quantite * ci.prix) AS total
    FROM commandes c
    JOIN users u         ON c.id_client      = u.id_user
    JOIN reservations r  ON c.id_reservation = r.id_reservation
    JOIN chambres ch     ON r.id_chambre     = ch.id_chambre
    LEFT JOIN commande_items ci ON ci.id_commande = c.id_commande
    $where
    GROUP BY c.id_commande
    ORDER BY c.created_at DESC
")->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Commandes</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="back-layout">
        <?php include '../partials/sidebar.php'; ?>
        <div class="main-content">
            <div class="topbar">
                <div class="topbar-title">
                    <h1><i class="fas fa-shopping-cart"></i> Commandes Restaurant</h1>
                    <p><?= count($commandes) ?> commande(s)</p>
                </div>
            </div>
            <div class="page-content">

                <?php if ($flash): ?>
                <div class="toast <?= $flash['type'] ?>" style="position:static;margin-bottom:16px;animation:none;">
                    <i class="fas fa-check-circle"></i> <?= $flash['msg'] ?></div>
                <?php endif; ?>

                <!-- Filtres -->
                <div class="d-flex gap-1 mb-3">
                    <a href="?" class="btn <?= !$filtre?'btn-primary':'btn-outline' ?> btn-sm">Toutes</a>
                    <a href="?filtre=EN_COURS" class="btn <?= $filtre==='EN_COURS'?'btn-primary':'btn-outline' ?> btn-sm"><i class="fas fa-clock"></i> En cours</a>
                    <a href="?filtre=SERVI" class="btn <?= $filtre==='SERVI'?'btn-primary':'btn-outline' ?> btn-sm"><i class="fas fa-check-circle"></i> Servi</a>
                    <a href="?filtre=ANNULE" class="btn <?= $filtre==='ANNULE'?'btn-primary':'btn-outline' ?> btn-sm"><i class="fas fa-times-circle"></i> Annulé</a>
                </div>

                <div class="table-card">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Client</th>
                                    <th>Chambre</th>
                                    <th>Articles</th>
                                    <th>Total</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($commandes as $cmd): ?>
                                <?php
                                $b = ['EN_COURS'=>'warning','SERVI'=>'success','ANNULE'=>'danger'];
                                $i = ['EN_COURS'=>'fa-clock','SERVI'=>'fa-check-circle','ANNULE'=>'fa-times-circle'];
                                $s = $cmd['statut'];
                                ?>
                                <tr>
                                    <td>#<?= $cmd['id_commande'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($cmd['prenom'].' '.$cmd['nom']) ?></strong><br>
                                        <small class="text-muted">Chambre N°<?= $cmd['numero'] ?></small>
                                    </td>
                                    <td>N°<?= htmlspecialchars($cmd['numero']) ?></td>
                                    <td>
                                        <a href="#" onclick="voirDetails(<?= $cmd['id_commande'] ?>)" class="btn btn-outline btn-sm">
                                            <i class="fas fa-receipt"></i> <?= $cmd['nb_articles'] ?> article(s)
                                        </a>
                                    </td>
                                    <td class="fw-700 text-primary"><?= number_format($cmd['total'],0,',',' ') ?> Ar</td>
                                    <td><span class="badge badge-<?= $b[$s] ?>"><i class="fas <?= $i[$s] ?>"></i> <?= $s ?></span></td>
                                    <td class="text-muted fs-sm"><?= date('d/m/Y H:i', strtotime($cmd['created_at'])) ?></td>
                                    <td>
                                        <?php if ($s === 'EN_COURS'): ?>
                                        <a href="?id=<?= $cmd['id_commande'] ?>&statut=SERVI" class="btn btn-primary btn-sm"><i class="fas fa-check"></i> Servir</a>
                                        <button onclick="confirmDelete('?id=<?= $cmd['id_commande'] ?>&statut=ANNULE', 'Annuler cette commande ?')" class="btn btn-danger btn-sm"><i class="fas fa-times"></i></button>
                                        <?php else: ?>
                                        <span class="text-muted fs-sm">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($commandes)): ?>
                                <tr><td colspan="8" class="text-center text-muted" style="padding:40px;">Aucune commande</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DÉTAILS COMMANDE -->
    <div class="modal-overlay" id="modalDetails">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fas fa-receipt"></i> Détails de la commande</h3>
                <button class="modal-close" onclick="closeModal('modalDetails')"><i class="fas fa-times"></i></button>
            </div>
            <div id="modalContent" style="min-height:100px;">
                <div class="text-center text-muted" style="padding:20px;">Chargement...</div>
            </div>
        </div>
    </div>

    <!-- MODAL SUPPRESSION/ANNULATION -->
    <div class="modal-overlay" id="modalDelete">
      <div class="modal-box">
        <div class="modal-confirm-body">
          <div class="confirm-icon"><i class="fas fa-exclamation-triangle"></i></div>
          <h4>Confirmer l'action</h4>
          <p id="deleteMessage">Cette action est irréversible.</p>
          <div class="modal-confirm-actions">
            <button onclick="closeModal('modalDelete')" class="btn btn-outline">Annuler</button>
            <button onclick="executeDelete()" class="btn btn-danger"><i class="fas fa-check"></i> Confirmer</button>
          </div>
        </div>
      </div>
    </div>

    <script src="../../js/main.js"></script>
    <script>
    function voirDetails(id) {
        openModal('modalDetails');
        fetch('get_details.php?id=' + id)
            .then(r => r.text())
            .then(html => {
                document.getElementById('modalContent').innerHTML = html;
            })
            .catch(() => {
                document.getElementById('modalContent').innerHTML =
                    '<p class="text-muted text-center">Erreur de chargement.</p>';
            });
    }
    </script>
</body>
</html>