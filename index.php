<?php
session_start();
if (isset($_SESSION['user'])) {
    $role = $_SESSION['user']['role'];
    if ($role === 'ADMIN' || $role === 'EMPLOYE') {
        header('Location: back/dashboard.php');
    } else {
        header('Location: front/index.php');
    }
} else {
    header('Location: front/login.php');
}
exit;