<?php
session_start();
require_once '../config/database.php';
if (!in_array($_SESSION['user']['role'],['ADMIN','EMPLOYE'])) {
    header('Location: ../front/login.php'); exit;
}

$db     = Database::getInstance()->getConnection();
$action = $_GET['action'] ?? '';
$id     = (int)($_GET['id'] ?? 0);

if ($action === 'delete' && $id) {
    $db->prepare("DELETE FROM menu WHERE id_menu=?")->execute([$id]);
    $_SESSION['flash'] = ['type'=>'success','msg'=>'🗑️ Article supprimé.'];
}

header('Location: ../back/menu/index.php');