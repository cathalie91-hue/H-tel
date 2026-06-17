<?php
session_start();
require_once '../../config/database.php';
if (!isset($_SESSION['user'])) exit;

$db = Database::getInstance()->getConnection();
$id = (int)($_GET['id'] ?? 0);

$items = $db->prepare("
    SELECT ci.*, m.nom, m.type
    FROM commande_items ci
    JOIN menu m ON ci.id_menu = m.id_menu
    WHERE ci.id_commande = ?
");
$items->execute([$id]);
$articles = $items->fetchAll();

$total = 0;
foreach ($articles as $a) $total += $a['quantite'] * $a['prix'];
?>
<div style="display:flex;flex-direction:column;gap:10px;">
  <?php foreach ($articles as $a): ?>
  <div style="display:flex;align-items:center;gap:12px;padding:12px;background:var(--body-bg);border-radius:10px;">
    <div style="font-size:20px;width:36px;height:36px;border-radius:8px;background:<?= $a['type']==='PLAT'?'#dbeafe':'#fef9e7' ?>;display:flex;align-items:center;justify-content:center;color:<?= $a['type']==='PLAT'?'#1e40af':'#92400e' ?>;">
      <i class="fas <?= $a['type']==='PLAT'?'fa-drumstick-bite':'fa-glass-whiskey' ?>"></i>
    </div>
    <div style="flex:1;">
      <div style="font-weight:600;font-size:14px;"><?= htmlspecialchars($a['nom']) ?></div>
      <div style="font-size:12px;color:var(--text-muted);">
        <?= number_format($a['prix'],0,',',' ') ?> Ar × <?= $a['quantite'] ?>
      </div>
    </div>
    <div style="font-weight:700;color:var(--primary-dark);">
      <?= number_format($a['quantite']*$a['prix'],0,',',' ') ?> Ar
    </div>
  </div>
  <?php endforeach; ?>

  <div style="display:flex;justify-content:space-between;padding:14px;background:var(--primary-light);border-radius:10px;margin-top:6px;">
    <span style="font-weight:700;font-size:16px;">Total</span>
    <span style="font-weight:800;font-size:18px;color:var(--primary-dark);">
      <?= number_format($total,0,',',' ') ?> Ar
    </span>
  </div>
</div>