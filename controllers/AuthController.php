<?php
session_start();
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: ../front/login.php');
    exit;
}
header('Location: ../front/login.php');