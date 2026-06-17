<?php
session_start();
require_once '../config/database.php';
if ($_SESSION['user']['role'] !== 'ADMIN') { header('Location: ../front/login.php'); exit; }

$db     = Database::getInstance()->getConnection();
$action = $_GET['action'] ?? '';
$id     = (int)($_GET['id'] ?? 0);

if ($action === 'delete' && $id && $id != $_SESSION['user']['id_user']) {
    $db->prepare("DELETE FROM users WHERE id_user=?")->execute([$id]);
    $_SESSION['flash'] = ['type'=>'success','msg'=>'🗑️ Utilisateur supprimé.'];
}

header('Location: ../back/users/index.php');