<?php

/**
 * Fonctions utilitaires pour l'application de gestion de bibliothèque
 */

// Nettoyer les entrées utilisateur
function clean_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Générer un message d'alerte
function alert_message($message, $type = 'info')
{
    return '<div class="alert alert-' . $type . '">' . $message . '</div>';
}

// Vérifier si un livre est disponible pour l'emprunt
function is_livre_disponible($pdo, $livre_id)
{
    $stmt = $pdo->prepare("SELECT quantite, emprunts_actifs FROM livres WHERE id = ?");
    $stmt->execute([$livre_id]);
    $livre = $stmt->fetch();

    return $livre && ($livre['quantite'] - $livre['emprunts_actifs'] > 0);
}

// Mettre à jour le nombre d'emprunts actifs pour un livre
function update_emprunts_actifs($pdo, $livre_id, $increment = true)
{
    $operation = $increment ? '+1' : '-1';
    $stmt = $pdo->prepare("UPDATE livres SET emprunts_actifs = emprunts_actifs $operation WHERE id = ?");
    return $stmt->execute([$livre_id]);
}

// Formater une date au format français
function format_date($date)
{
    if (!$date) return '';
    $timestamp = strtotime($date);
    return date('d/m/Y', $timestamp);
}

// Calculer le retard en jours
function calculer_retard($date_retour_prevue)
{
    $today = new DateTime();
    $retour = new DateTime($date_retour_prevue);
    $diff = $today->diff($retour);

    if ($today > $retour) {
        return $diff->days;
    }

    return 0;
}

// Calculer les frais de retard (1€ par jour de retard)
function calculer_frais_retard($date_retour_prevue)
{
    $retard = calculer_retard($date_retour_prevue);
    return $retard * 1; // 1€ par jour
}

// Vérifier si l'utilisateur a atteint la limite d'emprunts (max 5 livres)
function can_borrow($pdo, $user_id)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM emprunts WHERE utilisateur_id = ? AND date_retour IS NULL");
    $stmt->execute([$user_id]);
    $count = $stmt->fetchColumn();

    return $count < 5;
}
