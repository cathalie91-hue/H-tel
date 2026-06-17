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
    // Récupérer la chambre liée pour la libérer si nécessaire
    $res = $db->prepare("SELECT id_chambre, statut FROM reservations WHERE id_reservation=?");
    $res->execute([$id]);
    $reservation = $res->fetch();
    
    if ($reservation) {
        // Libérer la chambre si la réservation était active
        if (in_array($reservation['statut'], ['EN_ATTENTE', 'CONFIRMEE'])) {
            $db->prepare("UPDATE chambres SET statut='DISPONIBLE' WHERE id_chambre=?")->execute([$reservation['id_chambre']]);
        }
        $db->prepare("DELETE FROM reservations WHERE id_reservation=?")->execute([$id]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Réservation supprimée.'];
    }
}

if ($action === 'statut' && $id && isset($_GET['statut'])) {
    $statut = $_GET['statut'];
    if (in_array($statut, ['EN_ATTENTE', 'CONFIRMEE', 'ANNULEE', 'TERMINEE'])) {
        // Récupérer la chambre liée
        $res = $db->prepare("SELECT id_chambre FROM reservations WHERE id_reservation=?");
        $res->execute([$id]);
        $reservation = $res->fetch();
        
        $db->prepare("UPDATE reservations SET statut=? WHERE id_reservation=?")->execute([$statut, $id]);
        
        if ($reservation) {
            if ($statut === 'CONFIRMEE') {
                $db->prepare("UPDATE chambres SET statut='OCCUPEE' WHERE id_chambre=?")->execute([$reservation['id_chambre']]);
            } elseif (in_array($statut, ['ANNULEE', 'TERMINEE'])) {
                $db->prepare("UPDATE chambres SET statut='DISPONIBLE' WHERE id_chambre=?")->execute([$reservation['id_chambre']]);
            }
        }
        
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Réservation mise à jour.'];
    }
}

header('Location: ../back/reservations/index.php');
