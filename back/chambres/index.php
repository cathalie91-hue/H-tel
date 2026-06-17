<?php
session_start();
require_once '../../config/database.php';
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'],['ADMIN','EMPLOYE'])) {
    header('Location: ../../front/login.php'); exit;
}

$db = Database::getInstance()->getConnection();
$error = '';

// Traitement ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add'])) {
    $numero      = trim($_POST['numero'] ?? '');
    $id_cat      = $_POST['id_categorie'] ?? '';
    $prix        = $_POST['prix'] ?? '';
    $statut      = $_POST['statut'] ?? 'DISPONIBLE';
    $description = trim($_POST['description'] ?? '');
    $image       = '';

    if (!$numero || $prix === '') {
        $error = 'Numéro et prix sont requis.';
    } else {
        if (!empty($_FILES['image']['name'])) {
            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Erreur lors du téléchargement de l\'image.';
            } elseif ($_FILES['image']['size'] > 5242880) { // 5MB
                $error = 'L\'image est trop volumineuse (max 5MB).';
            } else {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','webp'];
                if (!in_array($ext, $allowed)) {
                    $error = 'Type de fichier non autorisé.';
                } else {
                    @mkdir('../../uploads/chambres', 0755, true);
                    $image = uniqid() . '.' . $ext;
                    if (!move_uploaded_file($_FILES['image']['tmp_name'], '../../uploads/chambres/' . $image)) {
                        $error = 'Impossible de sauvegarder l\'image.';
                    }
                }
            }
        }
        if (!$error) {
            $stmt = $db->prepare("INSERT INTO chambres (numero,id_categorie,prix,statut,image,description) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$numero, $id_cat ?: null, $prix, $statut, $image, $description]);
            $_SESSION['flash'] = ['type'=>'success','msg'=>'Chambre ajoutée avec succès !'];
            header('Location: index.php'); exit;
        }
    }
}

// Traitement édition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_edit'])) {
    $id          = (int)$_POST['id_chambre'];
    $numero      = trim($_POST['numero'] ?? '');
    $id_cat      = $_POST['id_categorie'] ?? null;
    $prix        = $_POST['prix'] ?? '';
    $statut      = $_POST['statut'] ?? 'DISPONIBLE';
    $description = trim($_POST['description'] ?? '');

    $old = $db->prepare("SELECT image FROM chambres WHERE id_chambre=?");
    $old->execute([$id]);
    $oldData = $old->fetch();
    $image = $oldData['image'] ?? '';

    if (!$numero || $prix === '') {
        $error = 'Numéro et prix sont requis.';
    } else {
        if (!empty($_FILES['image_edit']['name'])) {
            if ($_FILES['image_edit']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Erreur lors du téléchargement de l\'image.';
            } elseif ($_FILES['image_edit']['size'] > 5242880) { // 5MB
                $error = 'L\'image est trop volumineuse (max 5MB).';
            } else {
                $ext = strtolower(pathinfo($_FILES['image_edit']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','webp'];
                if (!in_array($ext, $allowed)) {
                    $error = 'Type de fichier non autorisé.';
                } else {
                    if ($image && file_exists('../../uploads/chambres/' . $image)) {
                        unlink('../../uploads/chambres/' . $image);
                    }
                    @mkdir('../../uploads/chambres', 0755, true);
                    $image = uniqid() . '.' . $ext;
                    if (!move_uploaded_file($_FILES['image_edit']['tmp_name'], '../../uploads/chambres/' . $image)) {
                        $error = 'Impossible de sauvegarder l\'image.';
                    }
                }
            }
        }
        if (!$error) {
            $db->prepare("UPDATE chambres SET numero=?, id_categorie=?, prix=?, statut=?, image=?, description=? WHERE id_chambre=?")
               ->execute([$numero, $id_cat ?: null, $prix, $statut, $image, $description, $id]);
            $_SESSION['flash'] = ['type'=>'success','msg'=>'Chambre modifiée avec succès !'];
            header('Location: index.php'); exit;
        }
    }
}

// Filtres
$filtre_statut = $_GET['statut'] ?? '';
$filtre_cat    = $_GET['categorie'] ?? '';

$where = ['1=1'];
$params = [];

if ($filtre_statut) {
    $where[] = 'c.statut = ?';
    $params[] = $filtre_statut;
}
if ($filtre_cat) {
    $where[] = 'c.id_categorie = ?';
    $params[] = $filtre_cat;
}

$sql = "SELECT c.*, cat.nom AS categorie FROM chambres c
        LEFT JOIN categories cat ON c.id_categorie = cat.id_categorie
        WHERE " . implode(' AND ', $where) . " ORDER BY c.numero";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$chambres = $stmt->fetchAll();

$categories = $db->query("SELECT * FROM categories")->fetchAll();

// Message flash
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Chambres — Back Office</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="back-layout">
  <?php include '../partials/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">
        <h1><i class="fas fa-bed"></i> Gestion des Chambres</h1>
        <p><?= count($chambres) ?> chambre(s) trouvée(s)</p>
      </div>
      <div class="topbar-actions">
        <?php if ($_SESSION['user']['role'] === 'ADMIN'): ?>
        <button onclick="openModal('modalAdd')" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter chambre</button>
        <?php endif; ?>
      </div>
    </div>

    <div class="page-content">

      <?php if ($flash): ?>
      <div class="toast <?= $flash['type'] ?>" style="position:static;margin-bottom:16px;animation:none;">
        <i class="fas fa-check-circle"></i> <?= $flash['msg'] ?>
      </div>
      <?php endif; ?>

      <?php if ($error): ?>
      <div class="toast error" style="position:static;margin-bottom:16px;animation:none;">
        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <!-- FILTRES -->
      <div class="form-card mb-3" style="padding:16px 20px;">
        <form method="GET" class="d-flex gap-2 align-center">
          <select name="statut" class="form-control form-select" style="width:200px;">
            <option value="">Tous les statuts</option>
            <option value="DISPONIBLE" <?= $filtre_statut==='DISPONIBLE'?'selected':'' ?>>Disponible</option>
            <option value="OCCUPEE"    <?= $filtre_statut==='OCCUPEE'?'selected':'' ?>>Occupée</option>
            <option value="MAINTENANCE"<?= $filtre_statut==='MAINTENANCE'?'selected':'' ?>>Maintenance</option>
          </select>
          <select name="categorie" class="form-control form-select" style="width:180px;">
            <option value="">Toutes catégories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id_categorie'] ?>" <?= $filtre_cat==$cat['id_categorie']?'selected':'' ?>>
              <?= htmlspecialchars($cat['nom']) ?>
            </option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrer</button>
          <a href="index.php" class="btn btn-outline">Réinitialiser</a>
        </form>
      </div>

      <!-- TABLEAU -->
      <div class="table-card">
        <div class="table-header">
          <h3><i class="fas fa-list"></i> Liste des chambres</h3>
        </div>
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>N° Chambre</th>
                <th>Catégorie</th>
                <th>Prix / nuit</th>
                <th>Statut</th>
                <th>Description</th>
                <?php if ($_SESSION['user']['role']==='ADMIN'): ?>
                <th>Actions</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($chambres as $ch): ?>
              <tr>
                <td><strong>N°<?= htmlspecialchars($ch['numero']) ?></strong></td>
                <td><?= htmlspecialchars($ch['categorie'] ?? '—') ?></td>
                <td class="fw-700 text-primary"><?= number_format($ch['prix'], 0, ',', ' ') ?> Ar</td>
                <td>
                  <?php
                  $b = ['DISPONIBLE'=>'success','OCCUPEE'=>'danger','MAINTENANCE'=>'warning'];
                  $i = ['DISPONIBLE'=>'fa-check-circle','OCCUPEE'=>'fa-circle','MAINTENANCE'=>'fa-wrench'];
                  ?>
                  <span class="badge badge-<?= $b[$ch['statut']] ?>">
                    <i class="fas <?= $i[$ch['statut']] ?>"></i> <?= $ch['statut'] ?>
                  </span>
                </td>
                <td class="text-muted fs-sm"><?= htmlspecialchars(substr($ch['description'] ?? '', 0, 60)) ?>...</td>
                <?php if ($_SESSION['user']['role']==='ADMIN'): ?>
                <td>
                  <button onclick="editChambre(<?= htmlspecialchars(json_encode($ch)) ?>)" class="btn btn-outline btn-sm btn-icon" title="Modifier"><i class="fas fa-edit"></i></button>
                  <button onclick="confirmDelete('../../controllers/ChambreController.php?action=delete&id=<?= $ch['id_chambre'] ?>', 'Supprimer cette chambre ?')" class="btn btn-danger btn-sm btn-icon" title="Supprimer"><i class="fas fa-trash-alt"></i></button>
                </td>
                <?php endif; ?>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($chambres)): ?>
              <tr><td colspan="6" class="text-center text-muted" style="padding:40px;">Aucune chambre trouvée</td></tr>
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
      <h3><i class="fas fa-plus-circle"></i> Ajouter une chambre</h3>
      <button class="modal-close" onclick="closeModal('modalAdd')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action_add" value="1">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Numéro de chambre *</label>
          <input type="text" name="numero" class="form-control" placeholder="101" required>
        </div>
        <div class="form-group">
          <label class="form-label">Catégorie</label>
          <select name="id_categorie" class="form-control form-select">
            <option value="">— Aucune —</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id_categorie'] ?>"><?= htmlspecialchars($cat['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Prix / nuit (Ar) *</label>
          <input type="number" name="prix" class="form-control" placeholder="80000" required min="0">
        </div>
        <div class="form-group">
          <label class="form-label">Statut</label>
          <select name="statut" class="form-control form-select">
            <option value="DISPONIBLE">Disponible</option>
            <option value="OCCUPEE">Occupée</option>
            <option value="MAINTENANCE">Maintenance</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3" placeholder="Description de la chambre..."></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Photo (JPG, PNG)</label>
        <input type="file" name="image" class="form-control" accept="image/*">
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
        <button type="button" onclick="closeModal('modalAdd')" class="btn btn-outline">Annuler</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL ÉDITION -->
<div class="modal-overlay" id="modalEdit">
  <div class="modal-box modal-lg">
    <div class="modal-header">
      <h3><i class="fas fa-edit"></i> Modifier la chambre</h3>
      <button class="modal-close" onclick="closeModal('modalEdit')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action_edit" value="1">
      <input type="hidden" name="id_chambre" id="edit_id">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Numéro de chambre *</label>
          <input type="text" name="numero" id="edit_numero" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Catégorie</label>
          <select name="id_categorie" id="edit_categorie" class="form-control form-select">
            <option value="">— Aucune —</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id_categorie'] ?>"><?= htmlspecialchars($cat['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Prix / nuit (Ar) *</label>
          <input type="number" name="prix" id="edit_prix" class="form-control" required min="0">
        </div>
        <div class="form-group">
          <label class="form-label">Statut</label>
          <select name="statut" id="edit_statut" class="form-control form-select">
            <option value="DISPONIBLE">Disponible</option>
            <option value="OCCUPEE">Occupée</option>
            <option value="MAINTENANCE">Maintenance</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Nouvelle photo <span class="text-muted fs-sm">(laisser vide = inchangée)</span></label>
        <input type="file" name="image_edit" class="form-control" accept="image/*">
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
        <button type="button" onclick="closeModal('modalEdit')" class="btn btn-outline">Annuler</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL SUPPRESSION -->
<div class="modal-overlay" id="modalDelete">
  <div class="modal-box">
    <div class="modal-confirm-body">
      <div class="confirm-icon"><i class="fas fa-trash-alt"></i></div>
      <h4>Confirmer la suppression</h4>
      <p id="deleteMessage">Cette action est irréversible.</p>
      <div class="modal-confirm-actions">
        <button onclick="closeModal('modalDelete')" class="btn btn-outline">Annuler</button>
        <button onclick="executeDelete()" class="btn btn-danger"><i class="fas fa-trash-alt"></i> Supprimer</button>
      </div>
    </div>
  </div>
</div>

<script src="../../js/main.js"></script>
<script>
function editChambre(ch) {
    document.getElementById('edit_id').value = ch.id_chambre;
    document.getElementById('edit_numero').value = ch.numero;
    document.getElementById('edit_categorie').value = ch.id_categorie || '';
    document.getElementById('edit_prix').value = ch.prix;
    document.getElementById('edit_statut').value = ch.statut;
    document.getElementById('edit_description').value = ch.description || '';
    openModal('modalEdit');
}
</script>
</body>
</html>