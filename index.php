<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && $_SESSION['role'] === 'admin';

// Déterminer la page à afficher
$page = isset($_GET['page']) ? $_GET['page'] : 'accueil';

// En-tête du site
include 'includes/header.php';

// Contrôle d'accès
if (in_array($page, ['livres', 'emprunts', 'retours', 'mes-emprunts']) && !$isLoggedIn) {
    $_SESSION['message'] = 'Veuillez vous connecter pour accéder à cette page.';
    header('Location: index.php?page=login');
    exit;
}

if (in_array($page, ['commandes', 'utilisateurs']) && !$isAdmin) {
    $_SESSION['message'] = 'Accès non autorisé.';
    header('Location: index.php');
    exit;
}

// Inclusion du contenu selon la page demandée
switch ($page) {
    case 'accueil':
        include 'pages/accueil.php';
        break;
    case 'login':
        include 'pages/login.php';
        break;
    case 'register':
        include 'pages/register.php';
        break;
    case 'logout':
        include 'pages/logout.php';
        break;
    case 'livres':
        include 'pages/livres.php';
        break;
    case 'emprunts':
        include 'pages/emprunts.php';
        break;
    case 'retours':
        include 'pages/retours.php';
        break;
    case 'commandes':
        include 'pages/commandes.php';
        break;
    case 'utilisateurs':
        include 'pages/utilisateurs.php';
        break;
    case 'mes-emprunts':
        include 'pages/mes-emprunts.php';
        break;
    default:
        include 'pages/404.php';
}

// Pied de page
include 'includes/footer.php';
