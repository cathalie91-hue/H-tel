<?php
session_start();
require_once '../../config/database.php';
if (!isset($_SESSION['user'])||!in_array($_SESSION['user']['role'],['ADMIN','EMPLOYE'])) {
    header('Location: ../../front/login.php'); exit;
}
$db    = Database::getInstance()->getConnection();
$error = '';

// Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add'])) {
    $nom         = trim($_POST['nom'] ?? '');
    $type        = $_POST['type'] ?? 'PLAT';
    $prix        = $_POST['prix'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $disponible  = isset($_POST['disponible']) ? 1 : 0;
    $image       = '';

    if (!$nom || $prix === '') {
        $error = 'Nom et prix sont requis.';
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
                    @mkdir('../../uploads/menu', 0755, true);
                    $image = uniqid() . '.' . $ext;
                    if (!move_uploaded_file($_FILES['image']['tmp_name'], '../../uploads/menu/' . $image)) {
                        $error = 'Impossible de sauvegarder l\'image.';
                    }
                }
            }
        }
        if (!$error) {
            $db->prepare("INSERT INTO menu (nom, type, prix, image, description, disponible) VALUES (?,?,?,?,?,?)")
               ->execute([$nom, $type, $prix, $image, $description, $disponible]);
            $_SESSION['flash'] = ['type'=>'success','msg'=>'Article ajouté au menu !'];
            header('Location: index.php'); exit;
        }
    }
}

// Édition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_edit'])) {
    $id          = (int)$_POST['id_menu'];
    $nom         = trim($_POST['nom'] ?? '');
    $type        = $_POST['type'] ?? 'PLAT';
    $prix        = $_POST['prix'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $disponible  = isset($_POST['disponible']) ? 1 : 0;

    $old = $db->prepare("SELECT image FROM menu WHERE id_menu=?");
    $old->execute([$id]);
    $oldData = $old->fetch();
    $image = $oldData['image'] ?? '';

    if (!$nom || $prix === '') {
        $error = 'Nom et prix sont requis.';
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
                    if ($image && file_exists('../../uploads/menu/' . $image)) {
                        unlink('../../uploads/menu/' . $image);
                    }
                    @mkdir('../../uploads/menu', 0755, true);
                    $image = uniqid() . '.' . $ext;
                    if (!move_uploaded_file($_FILES['image_edit']['tmp_name'], '../../uploads/menu/' . $image)) {
                        $error = 'Impossible de sauvegarder l\'image.';
                    }
                }
            }
        }
        if (!$error) {
            $db->prepare("UPDATE menu SET nom=?, type=?, prix=?, image=?, description=?, disponible=? WHERE id_menu=?")
               ->execute([$nom, $type, $prix, $image, $description, $disponible, $id]);
            $_SESSION['flash'] = ['type'=>'success','msg'=>'Article modifié avec succès !'];
            header('Location: index.php'); exit;
        }
    }
}

$type_filter = $_GET['type'] ?? '';
$where = $type_filter ? "WHERE type='" . addslashes($type_filter) . "'" : '';
$plats = $db->query("SELECT * FROM menu $where ORDER BY type, nom")->fetchAll();
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Menu</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="back-layout">
  <?php include '../partials/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">
        <h1><i class="fas fa-utensils"></i> Menu Restaurant</h1>
        <p><?= count($plats) ?> article(s)</p>
      </div>
      <div class="topbar-actions">
        <button onclick="openModal('modalAdd')" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter article</button>
      </div>
    </div>
    <div class="page-content">
      <?php if ($flash): ?>
      <div class="toast <?= $flash['type'] ?>" style="position:static;margin-bottom:16px;animation:none;"><i class="fas fa-check-circle"></i> <?= $flash['msg'] ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
      <div class="toast error" style="position:static;margin-bottom:16px;animation:none;"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <div class="d-flex gap-1 mb-3">
        <a href="?" class="btn <?= !$type_filter?'btn-primary':'btn-outline' ?> btn-sm">Tout</a>
        <a href="?type=PLAT" class="btn <?= $type_filter==='PLAT'?'btn-primary':'btn-outline' ?> btn-sm"><i class="fas fa-drumstick-bite"></i> Plats</a>
        <a href="?type=BOISSON" class="btn <?= $type_filter==='BOISSON'?'btn-primary':'btn-outline' ?> btn-sm"><i class="fas fa-glass-whiskey"></i> Boissons</a>
      </div>

      <div class="chambres-grid">
        <?php foreach ($plats as $p): ?>
        <div class="chambre-card" style="cursor:default;">
          <div class="card-img" style="height:160px;background-color:#f0f0f0;display:flex;align-items:center;justify-content:center;overflow:hidden;">
            <?php if ($p['image'] && file_exists('../../uploads/menu/' . $p['image'])): ?>
              <img src="../../uploads/menu/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['nom']) ?>" style="width:100%;height:100%;object-fit:cover;">
            <?php else: ?>
              <i class="fas fa-image" style="font-size:48px;color:#ccc;"></i>
            <?php endif; ?>
          </div>
          <div class="card-body">
            <div class="d-flex justify-between align-center mb-1">
              <span class="badge <?= $p['type']==='PLAT'?'badge-info':'badge-gold' ?>">
                <i class="fas <?= $p['type']==='PLAT'?'fa-drumstick-bite':'fa-glass-whiskey' ?>"></i> <?= $p['type'] === 'PLAT' ? 'Plat' : 'Boisson' ?>
              </span>
              <span class="badge <?= $p['disponible']?'badge-success':'badge-danger' ?>">
                <i class="fas <?= $p['disponible']?'fa-check-circle':'fa-times-circle' ?>"></i> <?= $p['disponible']?'Dispo':'Indispo' ?>
              </span>
            </div>
            <h3 class="card-title"><?= htmlspecialchars($p['nom']) ?></h3>
            <p class="fs-sm text-muted mb-2"><?= htmlspecialchars($p['description'] ?? '') ?></p>
            <div class="d-flex justify-between align-center mt-2">
              <div class="card-price"><?= number_format($p['prix'],0,',',' ') ?> Ar</div>
              <div class="d-flex gap-1">
                <button onclick='editMenu(<?= json_encode($p) ?>)' class="btn btn-outline btn-sm btn-icon"><i class="fas fa-edit"></i></button>
                <button onclick="confirmDelete('../../controllers/MenuController.php?action=delete&id=<?= $p['id_menu'] ?>', 'Supprimer cet article ?')" class="btn btn-danger btn-sm btn-icon"><i class="fas fa-trash-alt"></i></button>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- MODAL AJOUT -->
<div class="modal-overlay" id="modalAdd">
  <div class="modal-box modal-lg">
    <div class="modal-header">
      <h3><i class="fas fa-plus-circle"></i> Ajouter un article</h3>
      <button class="modal-close" onclick="closeModal('modalAdd')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action_add" value="1">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Nom de l'article *</label>
          <input type="text" name="nom" class="form-control" placeholder="Poulet grillé" required>
        </div>
        <div class="form-group">
          <label class="form-label">Type</label>
          <select name="type" class="form-control form-select">
            <option value="PLAT">Plat</option>
            <option value="BOISSON">Boisson</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Prix (Ar) *</label>
        <input type="number" name="prix" class="form-control" placeholder="15000" required min="0" step="0.01">
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3" placeholder="Description du plat..."></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Photo (JPG, PNG)</label>
        <input type="file" name="image" class="form-control" accept="image/*">
      </div>
      <div class="form-group d-flex align-center gap-2">
        <input type="checkbox" name="disponible" id="add_disponible" checked style="width:18px;height:18px;accent-color:var(--primary);">
        <label for="add_disponible" class="form-label" style="margin:0;cursor:pointer;">Disponible immédiatement</label>
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
      <h3><i class="fas fa-edit"></i> Modifier l'article</h3>
      <button class="modal-close" onclick="closeModal('modalEdit')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action_edit" value="1">
      <input type="hidden" name="id_menu" id="edit_id_menu">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Nom *</label>
          <input type="text" name="nom" id="edit_nom" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Type</label>
          <select name="type" id="edit_type" class="form-control form-select">
            <option value="PLAT">Plat</option>
            <option value="BOISSON">Boisson</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Prix (Ar) *</label>
        <input type="number" name="prix" id="edit_prix_menu" class="form-control" required min="0" step="0.01">
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea name="description" id="edit_desc_menu" class="form-control" rows="3"></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Nouvelle photo <span class="text-muted fs-sm">(laisser vide = inchangée)</span></label>
        <input type="file" name="image_edit" class="form-control" accept="image/*">
      </div>
      <div class="form-group d-flex align-center gap-2">
        <input type="checkbox" name="disponible" id="edit_disponible" style="width:18px;height:18px;accent-color:var(--primary);">
        <label for="edit_disponible" class="form-label" style="margin:0;cursor:pointer;">Disponible</label>
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
function editMenu(m) {
    document.getElementById('edit_id_menu').value = m.id_menu;
    document.getElementById('edit_nom').value = m.nom;
    document.getElementById('edit_type').value = m.type;
    document.getElementById('edit_prix_menu').value = m.prix;
    document.getElementById('edit_desc_menu').value = m.description || '';
    document.getElementById('edit_disponible').checked = !!parseInt(m.disponible);
    openModal('modalEdit');
}
</script>
</body>
</html>