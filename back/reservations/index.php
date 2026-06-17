<?php
session_start();
require_once '../../config/database.php';
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'],['ADMIN','EMPLOYE'])) {
    header('Location: ../../front/login.php'); exit;
}

$db = Database::getInstance()->getConnection();
$error = '';

// Ajout réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add'])) {
    $id_client  = (int)$_POST['id_client'];
    $id_chambre = (int)$_POST['id_chambre'];
    $date_arr   = $_POST['date_arrivee'];
    $date_dep   = $_POST['date_depart'];
    $statut     = $_POST['statut'] ?? 'EN_ATTENTE';

    if (!$id_client || !$id_chambre || !$date_arr || !$date_dep) {
        $error = 'Tous les champs sont requis.';
    } elseif ($date_dep <= $date_arr) {
        $error = 'La date de départ doit être après la date d\'arrivée.';
    } else {
        $check = $db->prepare("SELECT id_reservation FROM reservations WHERE id_chambre=? AND statut IN ('EN_ATTENTE','CONFIRMEE') AND NOT (date_depart<=? OR date_arrivee>=?)");
        $check->execute([$id_chambre, $date_arr, $date_dep]);
        if ($check->fetch()) {
            $error = 'Cette chambre est déjà réservée pour ces dates.';
        } else {
            $db->prepare("INSERT INTO reservations (id_client,id_chambre,date_arrivee,date_depart,statut) VALUES (?,?,?,?,?)")
               ->execute([$id_client, $id_chambre, $date_arr, $date_dep, $statut]);
            
            if ($statut === 'CONFIRMEE') {
                $db->prepare("UPDATE chambres SET statut='OCCUPEE' WHERE id_chambre=?")->execute([$id_chambre]);
            }
            
            $_SESSION['flash'] = ['type'=>'success','msg'=>'Réservation créée avec succès !'];
            header('Location: index.php'); exit;
        }
    }
}

// Modifier statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_edit'])) {
    $id = (int)$_POST['id_reservation'];
    $nouveau_statut = $_POST['statut'];
    
    $res = $db->prepare("SELECT * FROM reservations WHERE id_reservation=?");
    $res->execute([$id]);
    $r = $res->fetch();
    
    if ($r) {
        $db->prepare("UPDATE reservations SET statut=? WHERE id_reservation=?")->execute([$nouveau_statut, $id]);
        
        if ($nouveau_statut === 'CONFIRMEE') {
            $db->prepare("UPDATE chambres SET statut='OCCUPEE' WHERE id_chambre=?")->execute([$r['id_chambre']]);
        } elseif (in_array($nouveau_statut, ['ANNULEE','TERMINEE'])) {
            $db->prepare("UPDATE chambres SET statut='DISPONIBLE' WHERE id_chambre=?")->execute([$r['id_chambre']]);
        }
        
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Réservation mise à jour !'];
    }
    header('Location: index.php'); exit;
}

$filtre = $_GET['statut'] ?? '';
$where  = $filtre ? "WHERE r.statut = '" . addslashes($filtre) . "'" : '';

$reservations = $db->query("
    SELECT r.*, u.nom, u.prenom, u.email,
           ch.numero, ch.prix,
           DATEDIFF(r.date_depart, r.date_arrivee) AS nb_nuits
    FROM reservations r
    JOIN users u    ON r.id_client  = u.id_user
    JOIN chambres ch ON r.id_chambre = ch.id_chambre
    $where
    ORDER BY r.created_at DESC
")->fetchAll();

$clients  = $db->query("SELECT * FROM users WHERE role='CLIENT' ORDER BY nom")->fetchAll();
$chambres_dispo = $db->query("SELECT c.*, cat.nom AS categorie FROM chambres c LEFT JOIN categories cat ON c.id_categorie=cat.id_categorie WHERE c.statut='DISPONIBLE' ORDER BY c.numero")->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Réservations</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="back-layout">
  <?php include '../partials/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">
        <h1><i class="fas fa-calendar-alt"></i> Réservations</h1>
        <p><?= count($reservations) ?> réservation(s)</p>
      </div>
      <div class="topbar-actions">
        <button onclick="openModal('modalAdd')" class="btn btn-primary"><i class="fas fa-plus"></i> Nouvelle réservation</button>
      </div>
    </div>
    <div class="page-content">

      <?php if ($flash): ?>
      <div class="toast <?= $flash['type'] ?>" style="position:static;margin-bottom:16px;animation:none;"><i class="fas fa-check-circle"></i> <?= $flash['msg'] ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="toast error" style="position:static;margin-bottom:16px;animation:none;"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- Filtre statut -->
      <div class="d-flex gap-1 mb-3">
        <?php
        $statuts = [''=>'Toutes','EN_ATTENTE'=>'En attente','CONFIRMEE'=>'Confirmée','ANNULEE'=>'Annulée','TERMINEE'=>'Terminée'];
        $statut_icons = [''=>'','EN_ATTENTE'=>'fa-clock','CONFIRMEE'=>'fa-check-circle','ANNULEE'=>'fa-times-circle','TERMINEE'=>'fa-flag-checkered'];
        foreach ($statuts as $val => $label):
        ?>
        <a href="?statut=<?= $val ?>"
           class="btn <?= $filtre===$val?'btn-primary':'btn-outline' ?> btn-sm">
          <?php if ($statut_icons[$val]): ?><i class="fas <?= $statut_icons[$val] ?>"></i><?php endif; ?> <?= $label ?>
        </a>
        <?php endforeach; ?>
      </div>

      <div class="table-card">
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Client</th>
                <th>Chambre</th>
                <th>Arrivée</th>
                <th>Départ</th>
                <th>Nuits</th>
                <th>Montant</th>
                <th>Statut</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($reservations as $r): ?>
              <tr>
                <td>#<?= $r['id_reservation'] ?></td>
                <td>
                  <strong><?= htmlspecialchars($r['prenom'].' '.$r['nom']) ?></strong><br>
                  <small class="text-muted"><?= htmlspecialchars($r['email']) ?></small>
                </td>
                <td>N°<?= htmlspecialchars($r['numero']) ?></td>
                <td><?= date('d/m/Y', strtotime($r['date_arrivee'])) ?></td>
                <td><?= date('d/m/Y', strtotime($r['date_depart'])) ?></td>
                <td><?= $r['nb_nuits'] ?> nuit(s)</td>
                <td class="fw-700 text-primary"><?= number_format($r['nb_nuits'] * $r['prix'], 0, ',', ' ') ?> Ar</td>
                <td>
                  <?php
                  $b = ['EN_ATTENTE'=>'warning','CONFIRMEE'=>'success','ANNULEE'=>'danger','TERMINEE'=>'muted'];
                  $i = ['EN_ATTENTE'=>'fa-clock','CONFIRMEE'=>'fa-check-circle','ANNULEE'=>'fa-times-circle','TERMINEE'=>'fa-flag-checkered'];
                  $s = $r['statut'];
                  ?>
                  <span class="badge badge-<?= $b[$s] ?>"><i class="fas <?= $i[$s] ?>"></i> <?= $s ?></span>
                </td>
                <td>
                  <button onclick="editReservation(<?= $r['id_reservation'] ?>, '<?= $s ?>')" class="btn btn-outline btn-sm"><i class="fas fa-edit"></i> Modifier</button>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($reservations)): ?>
              <tr><td colspan="9" class="text-center text-muted" style="padding:40px;">Aucune réservation</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL AJOUT -->
<div class="modal-overlay" id="modalAdd">
  <div class="modal-box modal-lg">
    <div class="modal-header">
      <h3><i class="fas fa-plus-circle"></i> Nouvelle Réservation</h3>
      <button class="modal-close" onclick="closeModal('modalAdd')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action_add" value="1">
      <div class="form-group">
        <label class="form-label">Client *</label>
        <select name="id_client" class="form-control form-select" required>
          <option value="">— Sélectionner un client —</option>
          <?php foreach ($clients as $c): ?>
          <option value="<?= $c['id_user'] ?>"><?= htmlspecialchars($c['prenom'].' '.$c['nom']) ?> (<?= $c['email'] ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Chambre *</label>
        <select name="id_chambre" class="form-control form-select" required>
          <option value="">— Sélectionner une chambre —</option>
          <?php foreach ($chambres_dispo as $ch): ?>
          <option value="<?= $ch['id_chambre'] ?>">N°<?= $ch['numero'] ?> — <?= htmlspecialchars($ch['categorie']??'Standard') ?> — <?= number_format($ch['prix'],0,',',' ') ?> Ar/nuit</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Date d'arrivée *</label>
          <input type="date" name="date_arrivee" class="form-control" required min="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Date de départ *</label>
          <input type="date" name="date_depart" class="form-control" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Statut initial</label>
        <select name="statut" class="form-control form-select">
          <option value="EN_ATTENTE">En attente</option>
          <option value="CONFIRMEE">Confirmée</option>
        </select>
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Créer la réservation</button>
        <button type="button" onclick="closeModal('modalAdd')" class="btn btn-outline">Annuler</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL ÉDITION STATUT -->
<div class="modal-overlay" id="modalEdit">
  <div class="modal-box">
    <div class="modal-header">
      <h3><i class="fas fa-edit"></i> Modifier le statut</h3>
      <button class="modal-close" onclick="closeModal('modalEdit')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action_edit" value="1">
      <input type="hidden" name="id_reservation" id="edit_id_res">
      <div class="form-group">
        <label class="form-label">Nouveau statut</label>
        <select name="statut" id="edit_statut_res" class="form-control form-select" required>
          <option value="EN_ATTENTE">En attente</option>
          <option value="CONFIRMEE">Confirmée</option>
          <option value="ANNULEE">Annulée</option>
          <option value="TERMINEE">Terminée</option>
        </select>
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
        <button type="button" onclick="closeModal('modalEdit')" class="btn btn-outline">Annuler</button>
      </div>
    </form>
  </div>
</div>

<script src="../../js/main.js"></script>
<script>
function editReservation(id, statut) {
    document.getElementById('edit_id_res').value = id;
    document.getElementById('edit_statut_res').value = statut;
    openModal('modalEdit');
}
</script>
</body>
</html>