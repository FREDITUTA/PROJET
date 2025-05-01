<?php
// Page accessible uniquement aux utilisateurs connectés
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

$user_id = $_SESSION['user_id'];

// Récupérer les emprunts en cours
$stmt = $pdo->prepare("SELECT e.*, l.titre AS livre_titre, l.auteur AS livre_auteur
                       FROM emprunts e
                       JOIN livres l ON e.livre_id = l.id
                       WHERE e.utilisateur_id = ? AND e.date_retour IS NULL
                       ORDER BY e.date_retour_prevue ASC");
$stmt->execute([$user_id]);
$emprunts_en_cours = $stmt->fetchAll();

// Récupérer l'historique des emprunts
$stmt = $pdo->prepare("SELECT e.*, l.titre AS livre_titre, l.auteur AS livre_auteur
                       FROM emprunts e
                       JOIN livres l ON e.livre_id = l.id
                       WHERE e.utilisateur_id = ? AND e.date_retour IS NOT NULL
                       ORDER BY e.date_retour DESC
                       LIMIT 10");
$stmt->execute([$user_id]);
$historique_emprunts = $stmt->fetchAll();
?>

<h2>Mes emprunts</h2>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Emprunts en cours</h5>
            </div>
            <div class="card-body">
                <?php if (empty($emprunts_en_cours)): ?>
                    <div class="alert alert-info">Vous n'avez aucun emprunt en cours.</div>
                    <a href="index.php?page=livres" class="btn btn-primary">Parcourir le catalogue</a>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Livre</th>
                                    <th>Date d'emprunt</th>
                                    <th>Date de retour prévue</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($emprunts_en_cours as $emprunt): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($emprunt['livre_titre'] . ' (' . $emprunt['livre_auteur'] . ')'); ?></td>
                                        <td><?php echo format_date($emprunt['date_emprunt']); ?></td>
                                        <td><?php echo format_date($emprunt['date_retour_prevue']); ?></td>
                                        <td>
                                            <?php
                                            $retard = calculer_retard($emprunt['date_retour_prevue']);
                                            if ($retard > 0):
                                            ?>
                                                <span class="badge bg-danger">En retard (<?php echo $retard; ?> jour(s))</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">À jour</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <a href="index.php?page=livres" class="btn btn-primary">Emprunter d'autres livres</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5>Historique des emprunts</h5>
            </div>
            <div class="card-body">
                <?php if (empty($historique_emprunts)): ?>
                    <div class="alert alert-info">Vous n'avez aucun historique d'emprunt.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Livre</th>
                                    <th>Date d'emprunt</th>
                                    <th>Date de retour prévue</th>
                                    <th>Date de retour</th>
                                    <th>Frais de retard</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historique_emprunts as $emprunt): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($emprunt['livre_titre'] . ' (' . $emprunt['livre_auteur'] . ')'); ?></td>
                                        <td><?php echo format_date($emprunt['date_emprunt']); ?></td>
                                        <td><?php echo format_date($emprunt['date_retour_prevue']); ?></td>
                                        <td><?php echo format_date($emprunt['date_retour']); ?></td>
                                        <td>
                                            <?php if ($emprunt['frais_retard'] > 0): ?>
                                                <span class="text-danger"><?php echo $emprunt['frais_retard']; ?>€</span>
                                            <?php else: ?>
                                                <span class="text-success">0€</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>