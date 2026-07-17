<?php
session_start();
require_once '../../config/database.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'CLIENT') {
    header('Location: ../login.php'); exit;
}

$db   = Database::getInstance()->getConnection();
$user = $_SESSION['user'];

// Vérifier réservation confirmée
$res = $db->prepare("SELECT * FROM reservations WHERE id_client=? AND statut='CONFIRMEE' AND date_depart >= CURDATE() LIMIT 1");
$res->execute([$user['id_user']]);
$reservation = $res->fetch();

// Menu
$plats    = $db->query("SELECT * FROM menu WHERE type='PLAT'    AND disponible=1")->fetchAll();
$boissons = $db->query("SELECT * FROM menu WHERE type='BOISSON' AND disponible=1")->fetchAll();

// Panier session
if (!isset($_SESSION['panier'])) $_SESSION['panier'] = [];

// Ajouter au panier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    if (!$reservation) {
        $_SESSION['flash_menu'] = "Vous devez avoir une réservation active pour commander.";
        $_SESSION['flash_menu_type'] = "warning";
    } else {
        $id_menu = (int)$_POST['id_menu'];
        if (isset($_SESSION['panier'][$id_menu])) {
            $_SESSION['panier'][$id_menu]++;
        } else {
            $_SESSION['panier'][$id_menu] = 1;
        }
    }
}

// Supprimer du panier
if (isset($_GET['remove'])) {
    unset($_SESSION['panier'][(int)$_GET['remove']]);
}

// Passer commande
if (isset($_POST['passer_commande']) && $reservation && !empty($_SESSION['panier'])) {
    $stmt = $db->prepare("INSERT INTO commandes (id_client,id_reservation,statut) VALUES (?,?,'EN_COURS')");
    $stmt->execute([$user['id_user'], $reservation['id_reservation']]);
    $id_commande = $db->lastInsertId();

    foreach ($_SESSION['panier'] as $id_menu => $qty) {
        $item = $db->prepare("SELECT prix FROM menu WHERE id_menu=?");
        $item->execute([$id_menu]);
        $prix = $item->fetchColumn();
        $db->prepare("INSERT INTO commande_items (id_commande,id_menu,quantite,prix) VALUES (?,?,?,?)")
           ->execute([$id_commande, $id_menu, $qty, $prix]);
    }
    $_SESSION['panier'] = [];
    $_SESSION['flash_menu'] = "Commande passée avec succès !";
    $_SESSION['flash_menu_type'] = "success";
}

// Calculer total panier
$total_panier = 0;
$panier_details = [];
foreach ($_SESSION['panier'] as $id_menu => $qty) {
    $item = $db->prepare("SELECT * FROM menu WHERE id_menu=?");
    $item->execute([$id_menu]);
    $m = $item->fetch();
    if ($m) {
        $m['qty'] = $qty;
        $m['sous_total'] = $m['prix'] * $qty;
        $total_panier += $m['sous_total'];
        $panier_details[] = $m;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Restaurant — Menu</title>

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-EJ8BYRQW0H"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-EJ8BYRQW0H');
</script>

    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <header class="front-header">
        <div class="front-logo"><i class="fas fa-hotel"></i> Hôtel<span>Luxe</span></div>
        <nav class="front-nav">
            <a href="../index.php"><i class="fas fa-home"></i> Accueil</a>
            <a href="../chambres/index.php"><i class="fas fa-bed"></i> Chambres</a>
            <a href="index.php" style="color:var(--primary-dark);font-weight:700;"><i class="fas fa-utensils"></i> Restaurant</a>
            <a href="../reservation/index.php"><i class="fas fa-calendar-alt"></i> Réservations</a>
            <button onclick="toggleCart()" class="btn btn-primary btn-sm" style="position:relative;">
                <i class="fas fa-shopping-cart"></i> Panier
                <?php if (!empty($_SESSION['panier'])): ?>
                <span style="position:absolute;top:-6px;right:-6px;background:#ef4444;color:#fff;border-radius:50%;width:18px;height:18px;font-size:10px;display:flex;align-items:center;justify-content:center;font-weight:700;">
                    <?= array_sum($_SESSION['panier']) ?>
                </span>
                <?php endif; ?>
            </button>
        </nav>
    </header>

    <!-- FLASH -->
    <?php if (isset($_SESSION['flash_menu'])): ?>
    <div style="background:<?= ($_SESSION['flash_menu_type'] ?? '') === 'warning' ? '#fef3c7' : 'var(--primary-light)' ?>;border-left:4px solid <?= ($_SESSION['flash_menu_type'] ?? '') === 'warning' ? 'var(--warning)' : 'var(--primary)' ?>;padding:14px 60px;font-weight:600;">
        <i class="fas <?= ($_SESSION['flash_menu_type'] ?? '') === 'warning' ? 'fa-exclamation-triangle' : 'fa-check-circle' ?>"></i>
        <?= $_SESSION['flash_menu'] ?> <?php unset($_SESSION['flash_menu']); unset($_SESSION['flash_menu_type']); ?>
    </div>
    <?php endif; ?>

    <?php if (!$reservation): ?>
    <div style="background:#fef3c7;border-left:4px solid var(--warning);padding:14px 60px;font-size:14px;">
        <i class="fas fa-exclamation-triangle"></i> Vous devez avoir une réservation confirmée et en cours pour commander. <a href="../reservation/index.php">Réserver une chambre</a>
    </div>
    <?php endif; ?>

    <main style="padding:40px 60px;">

        <!-- Plats -->
        <h2 style="font-size:22px;font-weight:800;margin-bottom:20px;"><i class="fas fa-drumstick-bite" style="color:var(--primary);margin-right:8px;"></i> Nos Plats</h2>
        <div class="chambres-grid" style="margin-bottom:40px;">
            <?php foreach ($plats as $p): ?>
            <div class="chambre-card" style="cursor:default;">
                <div class="card-img" style="height:140px;background-color:#f0f0f0;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                    <?php if ($p['image'] && file_exists('../../uploads/menu/' . $p['image'])): ?>
                        <img src="../../uploads/menu/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['nom']) ?>" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                        <i class="fas fa-image" style="font-size:48px;color:#ccc;"></i>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <h3 class="card-title"><?= htmlspecialchars($p['nom']) ?></h3>
                    <p class="fs-sm text-muted mb-2"><?= htmlspecialchars($p['description'] ?? '') ?></p>
                    <div class="d-flex justify-between align-center">
                        <div class="card-price"><?= number_format($p['prix'],0,',',' ') ?> Ar</div>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id_menu" value="<?= $p['id_menu'] ?>">
                            <button type="submit" name="add_item" class="btn btn-primary btn-sm"
                                <?= !$reservation?'disabled':'' ?>>
                                <i class="fas fa-plus"></i> Ajouter
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Boissons -->
        <h2 style="font-size:22px;font-weight:800;margin-bottom:20px;"><i class="fas fa-glass-whiskey" style="color:var(--primary);margin-right:8px;"></i> Boissons</h2>
        <div class="chambres-grid">
            <?php foreach ($boissons as $b): ?>
            <div class="chambre-card" style="cursor:default;">
                <div class="card-img" style="height:140px;background-color:#f0f0f0;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                    <?php if ($b['image'] && file_exists('../../uploads/menu/' . $b['image'])): ?>
                        <img src="../../uploads/menu/<?= htmlspecialchars($b['image']) ?>" alt="<?= htmlspecialchars($b['nom']) ?>" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                        <i class="fas fa-image" style="font-size:48px;color:#ccc;"></i>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <h3 class="card-title"><?= htmlspecialchars($b['nom']) ?></h3>
                    <p class="fs-sm text-muted mb-2"><?= htmlspecialchars($b['description'] ?? '') ?></p>
                    <div class="d-flex justify-between align-center">
                        <div class="card-price"><?= number_format($b['prix'],0,',',' ') ?> Ar</div>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id_menu" value="<?= $b['id_menu'] ?>">
                            <button type="submit" name="add_item" class="btn btn-primary btn-sm"
                                <?= !$reservation?'disabled':'' ?>>
                                <i class="fas fa-plus"></i> Ajouter
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </main>

    <!-- PANIER PANEL -->
    <div class="cart-panel" id="cartPanel">
        <div class="cart-header">
            <h3><i class="fas fa-shopping-cart" style="margin-right:8px;color:var(--primary);"></i> Mon panier</h3>
            <button onclick="toggleCart()" class="modal-close"><i class="fas fa-times"></i></button>
        </div>

        <div class="cart-items">
            <?php if (empty($panier_details)): ?>
            <div class="text-center text-muted" style="padding:40px 0;">
                <div style="font-size:40px;margin-bottom:12px;color:var(--primary);"><i class="fas fa-shopping-cart"></i></div>
                Panier vide
            </div>
            <?php else: ?>
            <?php foreach ($panier_details as $item): ?>
            <div class="cart-item">
                <div style="font-size:20px;width:36px;height:36px;border-radius:8px;background:<?= $item['type']==='PLAT'?'#dbeafe':'#fef9e7' ?>;display:flex;align-items:center;justify-content:center;color:<?= $item['type']==='PLAT'?'#1e40af':'#92400e' ?>;">
                    <i class="fas <?= $item['type']==='PLAT'?'fa-drumstick-bite':'fa-glass-whiskey' ?>"></i>
                </div>
                <div style="flex:1;">
                    <div class="fw-700 fs-sm"><?= htmlspecialchars($item['nom']) ?></div>
                    <div class="text-muted fs-sm"><?= number_format($item['prix'],0,',',' ') ?> Ar x <?= $item['qty'] ?></div>
                </div>
                <div class="fw-700 text-primary"><?= number_format($item['sous_total'],0,',',' ') ?> Ar</div>
                <a href="?remove=<?= $item['id_menu'] ?>" class="btn btn-danger btn-sm btn-icon"><i class="fas fa-times"></i></a>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="cart-footer">
            <div class="cart-total">
                <span>Total</span>
                <span><?= number_format($total_panier,0,',',' ') ?> Ar</span>
            </div>
            <?php if (!empty($panier_details) && $reservation): ?>
            <form method="POST">
                <button type="submit" name="passer_commande" class="btn btn-primary w-100 btn-lg">
                    <i class="fas fa-check-circle"></i> Confirmer la commande
                </button>
            </form>
            <?php elseif (!empty($panier_details)): ?>
            <div class="text-center text-muted fs-sm">Réservation active requise</div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function toggleCart() {
        document.getElementById('cartPanel').classList.toggle('open');
    }
    </script>
</body>
</html>