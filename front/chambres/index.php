<?php
session_start();
require_once '../../config/database.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'CLIENT') {
    header('Location: ../login.php'); exit;
}

$db   = Database::getInstance()->getConnection();
$user = $_SESSION['user'];

// Filtres
$filtre_cat = $_GET['categorie'] ?? '';
$arrivee    = $_GET['arrivee'] ?? '';
$depart     = $_GET['depart'] ?? '';

$where  = ["c.statut = 'DISPONIBLE'"];
$params = [];

if ($filtre_cat) {
    $where[]  = 'c.id_categorie = ?';
    $params[] = $filtre_cat;
}

if ($arrivee && $depart) {
    $where[] = "c.id_chambre NOT IN (
        SELECT id_chambre FROM reservations
        WHERE statut IN ('EN_ATTENTE','CONFIRMEE')
        AND NOT (date_depart <= ? OR date_arrivee >= ?)
    )";
    $params[] = $arrivee;
    $params[] = $depart;
}

$sql = "SELECT c.*, cat.nom AS categorie
        FROM chambres c
        LEFT JOIN categories cat ON c.id_categorie = cat.id_categorie
        WHERE " . implode(' AND ', $where) . "
        ORDER BY c.prix ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$chambres = $stmt->fetchAll();

$categories = $db->query("SELECT * FROM categories")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Chambres disponibles</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <header class="front-header">
        <div class="front-logo"><i class="fas fa-hotel"></i> Hôtel<span>Luxe</span></div>
        <nav class="front-nav">
            <a href="../index.php"><i class="fas fa-home"></i> Accueil</a>
            <a href="index.php" style="color:var(--primary-dark);font-weight:700;"><i class="fas fa-bed"></i> Chambres</a>
            <a href="../menu/index.php"><i class="fas fa-utensils"></i> Restaurant</a>
            <a href="../reservation/index.php"><i class="fas fa-calendar-alt"></i> Réservations</a>
            <a href="../profil/index.php"><i class="fas fa-user"></i> Profil</a>
            <a href="../../controllers/AuthController.php?action=logout" class="btn btn-outline btn-sm">Déconnexion</a>
        </nav>
    </header>

    <main style="padding:40px 60px;">

        <div class="d-flex justify-between align-center mb-3">
            <div>
                <h1 style="font-size:28px;font-weight:800;"><i class="fas fa-bed" style="color:var(--primary);margin-right:8px;"></i> Nos Chambres</h1>
                <p class="text-muted"><?= count($chambres) ?> chambre(s) disponible(s)</p>
            </div>
        </div>

        <!-- FILTRES -->
        <div class="form-card mb-3" style="padding:16px 20px;">
            <form method="GET" class="d-flex gap-2 align-center" style="flex-wrap:wrap;">
                <input type="date" name="arrivee" class="form-control" style="width:180px;" placeholder="Arrivée"
                    value="<?= htmlspecialchars($arrivee) ?>" min="<?= date('Y-m-d') ?>">
                <input type="date" name="depart" class="form-control" style="width:180px;" placeholder="Départ"
                    value="<?= htmlspecialchars($depart) ?>">
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

        <!-- GRILLE -->
        <?php if (empty($chambres)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-bed"></i></div>
            <h3>Aucune chambre disponible</h3>
            <p class="text-muted">Essayez d'autres dates ou catégories.</p>
        </div>
        <?php else: ?>
        <div class="chambres-grid">
            <?php foreach ($chambres as $ch): ?>
            <div class="chambre-card">
                <div class="card-img">
                    <?php if ($ch['image'] && file_exists('../../uploads/chambres/' . $ch['image'])): ?>
                    <img src="../../uploads/chambres/<?= htmlspecialchars($ch['image']) ?>" alt="Chambre"
                        style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                    <div style="width:100%;height:100%;background-color:#f0f0f0;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-image" style="font-size:48px;color:#ccc;"></i>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <p class="card-cat"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($ch['categorie'] ?? 'Standard') ?> — N°<?= $ch['numero'] ?></p>
                    <h3 class="card-title">Chambre <?= htmlspecialchars($ch['categorie'] ?? '') ?></h3>
                    <p class="fs-sm text-muted mb-2"><?= htmlspecialchars(substr($ch['description'] ?? '', 0, 80)) ?>...</p>
                    <div class="d-flex justify-between align-center mt-2">
                        <div class="card-price"><?= number_format($ch['prix'],0,',',' ') ?> Ar <span>/ nuit</span></div>
                        <a href="../reservation/index.php?id_chambre=<?= $ch['id_chambre'] ?><?= $arrivee?"&arrivee=$arrivee":'' ?><?= $depart?"&depart=$depart":'' ?>"
                            class="btn btn-primary btn-sm"><i class="fas fa-calendar-alt"></i> Réserver</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </main>
</body>
</html>