<?php
// Page accessible uniquement aux administrateurs
if ($_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Traitement du retour d'un livre
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'retourner') {
    $emprunt_id = (int) $_POST['emprunt_id'];

    // Récupérer l'emprunt
    $stmt = $pdo->prepare("SELECT * FROM emprunts WHERE id = ? AND date_retour IS NULL");
    $stmt->execute([$emprunt_id]);
    $emprunt = $stmt->fetch();

    if ($emprunt) {
        // Calculer les frais de retard
        $frais_retard = calculer_frais_retard($emprunt['date_retour_prevue']);

        // Mettre à jour la date de retour
        $stmt = $pdo->prepare("UPDATE emprunts SET date_retour = NOW(), frais_retard = ? WHERE id = ?");
        $success = $stmt->execute([$frais_retard, $emprunt_id]);

        if ($success) {
            // Mettre à jour le nombre d'emprunts actifs pour ce livre
            update_emprunts_actifs($pdo, $emprunt['livre_id'], false);

            $_SESSION['message'] = 'Le livre a été retourné avec succès.';
            if ($frais_retard > 0) {
                $_SESSION['message'] .= ' Frais de retard: ' . $frais_retard . '€';
            }
            header('Location: index.php?page=emprunts');
            exit;
        }
    } else {
        $_SESSION['message'] = 'Cet emprunt n\'existe pas ou a déjà été retourné.';
        header('Location: index.php?page=emprunts');
        exit;
    }
}

// Si un emprunt_id est spécifié dans l'URL, on affiche le formulaire de retour
$emprunt = null;
if (isset($_GET['emprunt_id'])) {
    $emprunt_id = (int) $_GET['emprunt_id'];

    $stmt = $pdo->prepare("SELECT e.*, 
                                 u.nom AS utilisateur_nom, u.prenom AS utilisateur_prenom, 
                                 l.titre AS livre_titre, l.auteur AS livre_auteur
                          FROM emprunts e
                          JOIN utilisateurs u ON e.utilisateur_id = u.id
                          JOIN livres l ON e.livre_id = l.id
                          WHERE e.id = ? AND e.date_retour IS NULL");
    $stmt->execute([$emprunt_id]);
    $emprunt = $stmt->fetch();

    if (!$emprunt) {
        $_SESSION['message'] = 'Cet emprunt n\'existe pas ou a déjà été retourné.';
        header('Location: index.php?page=emprunts');
        exit;
    }

    // Calculer les frais de retard
    $retard = calculer_retard($emprunt['date_retour_prevue']);
    $frais_retard = calculer_frais_retard($emprunt['date_retour_prevue']);
}

// Sinon, on affiche la liste des emprunts en cours
else {
    $stmt = $pdo->prepare("SELECT e.*, 
                                 u.nom AS utilisateur_nom, u.prenom AS utilisateur_prenom, 
                                 l.titre AS livre_titre, l.auteur AS livre_auteur
                          FROM emprunts e
                          JOIN utilisateurs u ON e.utilisateur_id = u.id
                          JOIN livres l ON e.livre_id = l.id
                          WHERE e.date_retour IS NULL
                          ORDER BY e.date_retour_prevue ASC");
    $stmt->execute();
    $emprunts = $stmt->fetchAll();
}
?>

<h2>Gestion des retours</h2>

<?php if ($emprunt): ?>
    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5>Retour de livre</h5>
                </div>
                <div class="card-body">
                    <p><strong>Livre:</strong> <?php echo htmlspecialchars($emprunt['livre_titre']); ?></p>
                    <p><strong>Auteur:</strong> <?php echo htmlspecialchars($emprunt['livre_auteur']); ?></p>
                    <p><strong>Emprunté par:</strong> <?php echo htmlspecialchars($emprunt['utilisateur_prenom'] . ' ' . $emprunt['utilisateur_nom']); ?></p>
                    <p><strong>Date d'emprunt:</strong> <?php echo format_date($emprunt['date_emprunt']); ?></p>
                    <p><strong>Date de retour prévue:</strong> <?php echo format_date($emprunt['date_retour_prevue']); ?></p>

                    <?php if ($retard > 0): ?>
                        <div class="alert alert-warning">
                            <p><strong>Retard:</strong> <?php echo $retard; ?> jour(s)</p>
                            <p><strong>Frais de retard:</strong> <?php echo $frais_retard; ?>€</p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <p>Le livre est retourné dans les délais.</p>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="action" value="retourner">
                        <input type="hidden" name="emprunt_id" value="<?php echo $emprunt['id']; ?>">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Confirmer le retour</button>
                            <a href="index.php?page=emprunts" class="btn btn-outline-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Emprunts en cours</h5>

            <?php if (empty($emprunts)): ?>
                <div class="alert alert-info">Aucun emprunt en cours.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Utilisateur</th>
                                <th>Livre</th>
                                <th>Date d'emprunt</th>
                                <th>Date de retour prévue</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($emprunts as $e): ?>
                                <tr>
                                    <td><?php echo $e['id']; ?></td>
                                    <td><?php echo htmlspecialchars($e['utilisateur_prenom'] . ' ' . $e['utilisateur_nom']); ?></td>
                                    <td><?php echo htmlspecialchars($e['livre_titre'] . ' (' . $e['livre_auteur'] . ')'); ?></td>
                                    <td><?php echo format_date($e['date_emprunt']); ?></td>
                                    <td><?php echo format_date($e['date_retour_prevue']); ?></td>
                                    <td>
                                        <?php if (strtotime($e['date_retour_prevue']) < time()): ?>
                                            <span class="badge bg-danger">En retard</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">En cours</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="index.php?page=retours&emprunt_id=<?php echo $e['id']; ?>" class="btn btn-sm btn-primary">Retourner</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>