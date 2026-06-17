<?php
session_start();
require_once '../../config/database.php';
if ($_SESSION['user']['role'] !== 'ADMIN') { header('Location: ../dashboard.php'); exit; }

$db    = Database::getInstance()->getConnection();
$error = '';

// Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add'])) {
    $nom       = trim($_POST['nom'] ?? '');
    $prenom    = trim($_POST['prenom'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $role      = $_POST['role'] ?? 'CLIENT';
    $password  = $_POST['password'] ?? '';

    if (!$nom || !$prenom || !$email || !$password) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } else {
        $check = $db->prepare("SELECT id_user FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'Cet email est déjà utilisé.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $db->prepare("INSERT INTO users (nom, prenom, email, password, role, telephone) VALUES (?,?,?,?,?,?)")
               ->execute([$nom, $prenom, $email, $hash, $role, $telephone]);
            $_SESSION['flash'] = ['type'=>'success','msg'=>'Utilisateur ajouté avec succès !'];
            header('Location: index.php'); exit;
        }
    }
}

// Édition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_edit'])) {
    $id        = (int)$_POST['id_user'];
    $nom       = trim($_POST['nom'] ?? '');
    $prenom    = trim($_POST['prenom'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $role      = $_POST['role'] ?? 'CLIENT';
    $password  = $_POST['password'] ?? '';

    if (!$nom || !$prenom || !$email) {
        $error = 'Nom, prénom et email sont requis.';
    } else {
        if ($password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $db->prepare("UPDATE users SET nom=?, prenom=?, email=?, telephone=?, role=?, password=? WHERE id_user=?")
               ->execute([$nom, $prenom, $email, $telephone, $role, $hash, $id]);
        } else {
            $db->prepare("UPDATE users SET nom=?, prenom=?, email=?, telephone=?, role=? WHERE id_user=?")
               ->execute([$nom, $prenom, $email, $telephone, $role, $id]);
        }
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Utilisateur modifié avec succès !'];
        header('Location: index.php'); exit;
    }
}

$users = $db->query("SELECT * FROM users ORDER BY role, nom")->fetchAll();
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Utilisateurs</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="back-layout">
  <?php include '../partials/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">
        <h1><i class="fas fa-users"></i> Utilisateurs</h1>
        <p><?= count($users) ?> utilisateur(s)</p>
      </div>
      <div class="topbar-actions">
        <button onclick="openModal('modalAdd')" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter utilisateur</button>
      </div>
    </div>
    <div class="page-content">
      <?php if ($flash): ?>
      <div class="toast <?= $flash['type'] ?>" style="position:static;margin-bottom:16px;animation:none;"><i class="fas fa-check-circle"></i> <?= $flash['msg'] ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="toast error" style="position:static;margin-bottom:16px;animation:none;"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <div class="table-card">
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Utilisateur</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Téléphone</th>
                <th>Inscription</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
              <tr>
                <td>
                  <div class="d-flex align-center gap-2">
                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary-dark));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;flex-shrink:0;">
                      <?= strtoupper(substr($u['prenom'],0,1)) ?>
                    </div>
                    <div>
                      <div class="fw-700"><?= htmlspecialchars($u['prenom'].' '.$u['nom']) ?></div>
                    </div>
                  </div>
                </td>
                <td class="text-muted"><?= htmlspecialchars($u['email']) ?></td>
                <td>
                  <?php $rb = ['ADMIN'=>'danger','EMPLOYE'=>'info','CLIENT'=>'success']; ?>
                  <span class="badge badge-<?= $rb[$u['role']] ?>"><?= $u['role'] ?></span>
                </td>
                <td><?= htmlspecialchars($u['telephone'] ?? '—') ?></td>
                <td class="text-muted fs-sm"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                <td>
                  <button onclick='editUser(<?= json_encode($u) ?>)' class="btn btn-outline btn-sm btn-icon"><i class="fas fa-edit"></i></button>
                  <?php if ($u['id_user'] != $_SESSION['user']['id_user']): ?>
                  <button onclick="confirmDelete('../../controllers/UserController.php?action=delete&id=<?= $u['id_user'] ?>', 'Supprimer cet utilisateur ?')" class="btn btn-danger btn-sm btn-icon"><i class="fas fa-trash-alt"></i></button>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
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
      <h3><i class="fas fa-plus-circle"></i> Ajouter un utilisateur</h3>
      <button class="modal-close" onclick="closeModal('modalAdd')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action_add" value="1">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Nom *</label>
          <input type="text" name="nom" class="form-control" placeholder="Rakoto" required>
        </div>
        <div class="form-group">
          <label class="form-label">Prénom *</label>
          <input type="text" name="prenom" class="form-control" placeholder="Jean" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Email *</label>
        <input type="email" name="email" class="form-control" placeholder="jean@email.com" required>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Téléphone</label>
          <input type="text" name="telephone" class="form-control" placeholder="+261 34 000 0000">
        </div>
        <div class="form-group">
          <label class="form-label">Rôle</label>
          <select name="role" class="form-control form-select">
            <option value="CLIENT">Client</option>
            <option value="EMPLOYE">Employé</option>
            <option value="ADMIN">Admin</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Mot de passe *</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
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
      <h3><i class="fas fa-edit"></i> Modifier utilisateur</h3>
      <button class="modal-close" onclick="closeModal('modalEdit')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action_edit" value="1">
      <input type="hidden" name="id_user" id="edit_id_user">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Nom *</label>
          <input type="text" name="nom" id="edit_nom_user" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Prénom *</label>
          <input type="text" name="prenom" id="edit_prenom_user" class="form-control" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Email *</label>
        <input type="email" name="email" id="edit_email_user" class="form-control" required>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Téléphone</label>
          <input type="text" name="telephone" id="edit_tel_user" class="form-control">
        </div>
        <div class="form-group">
          <label class="form-label">Rôle</label>
          <select name="role" id="edit_role_user" class="form-control form-select">
            <option value="CLIENT">Client</option>
            <option value="EMPLOYE">Employé</option>
            <option value="ADMIN">Admin</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Nouveau mot de passe <span class="text-muted fs-sm">(laisser vide = inchangé)</span></label>
        <input type="password" name="password" class="form-control" placeholder="••••••••">
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
function editUser(u) {
    document.getElementById('edit_id_user').value = u.id_user;
    document.getElementById('edit_nom_user').value = u.nom;
    document.getElementById('edit_prenom_user').value = u.prenom;
    document.getElementById('edit_email_user').value = u.email;
    document.getElementById('edit_tel_user').value = u.telephone || '';
    document.getElementById('edit_role_user').value = u.role;
    openModal('modalEdit');
}
</script>
</body>
</html>