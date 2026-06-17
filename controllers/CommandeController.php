<?php
session_start();
require_once '../config/database.php';
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['ADMIN', 'EMPLOYE'])) {
    header('Location: ../front/login.php'); exit;
}

$db     = Database::getInstance()->getConnection();
$action = $_GET['action'] ?? '';
$id     = (int)($_GET['id'] ?? 0);

if ($action === 'delete' && $id) {
    // Supprimer les items de la commande d'abord
    $db->prepare("DELETE FROM commande_items WHERE id_commande=?")->execute([$id]);
    // Supprimer la commande
    $db->prepare("DELETE FROM commandes WHERE id_commande=?")->execute([$id]);
    $_SESSION['flash'] = ['type'=>'success','msg'=>'Commande supprimée.'];
}

if ($action === 'statut' && $id && isset($_GET['statut'])) {
    $statut = $_GET['statut'];
    if (in_array($statut, ['EN_COURS', 'SERVI', 'ANNULE'])) {
        $db->prepare("UPDATE commandes SET statut=? WHERE id_commande=?")->execute([$statut, $id]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Statut de la commande mis à jour.'];
    }
}

header('Location: ../back/commandes/index.php');
