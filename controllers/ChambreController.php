<?php
session_start();
require_once '../config/database.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') {
    header('Location: ../front/login.php'); exit;
}

$db     = Database::getInstance()->getConnection();
$action = $_GET['action'] ?? '';
$id     = (int)($_GET['id'] ?? 0);

if ($action === 'delete' && $id) {
    $db->prepare("DELETE FROM chambres WHERE id_chambre=?")->execute([$id]);
    $_SESSION['flash'] = ['type'=>'success','msg'=>'🗑️ Chambre supprimée.'];
}

header('Location: ../back/chambres/index.php');