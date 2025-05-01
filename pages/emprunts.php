<?php
// Page accessible uniquement aux administrateurs
if ($_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Traitement des filtres
$status = isset($_GET['status']) ? clean_input($_GET['status']) : 'all';
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';

// Construction de la requête SQL
$sql = "SELECT e.*, 
               u.nom AS utilisateur_nom, u.prenom AS utilisateur_prenom, 
               l.titre AS livre_titre, l.auteur AS livre_auteur
        FROM emprunts e
        JOIN utilisateurs u ON e.utilisateur_id = u.id
        JOIN livres l ON e.livre_id = l.id
        WHERE 1=1";
$params = [];

if ($status === 'en_cours') {
    $sql .= " AND e.date_retour IS NULL";
} elseif ($status === 'retournes') {
    $sql .= " AND e.date_retour IS NOT NULL";
} elseif ($status === 'en_retard') {
    $sql .= " AND e.date_retour IS NULL AND e.date_retour_prevue < CURDATE()";
}

if (!empty($search)) {
    $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR l.titre LIKE ? OR l.auteur LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY e.date_emprunt DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$emprunts = $stmt->fetchAll();
?>

<h2>Gestion des emprunts</h2>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="index.php">
                    <input type="hidden" name="page" value="emprunts">
                    <div class="row">
                        <div class="col-md-5">
                            <input type="text" name="search" class="form-control" placeholder="Rechercher par utilisateur ou livre" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-4">
                            <select name="status" class="form-select">
                                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>Tous les emprunts</option>
                                <option value="en_cours" <?php echo $status === 'en_cours' ? 'selected' : ''; ?>>Emprunts en cours</option>
                                <option value="retournes" <?php echo $status === 'retournes' ? 'selected' : ''; ?>>Livres retournés</option>
                                <option value="en_retard" <?php echo $status === 'en_retard' ? 'selected' : ''; ?>>Emprunts en retard</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Utilisateur</th>
                <th>Livre</th>
                <th>Date d'emprunt</th>
                <th>Date de retour prévue</th>
                <th>Date de retour</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($emprunts)): ?>
                <tr>
                    <td colspan="8" class="text-center">Aucun emprunt trouvé.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($emprunts as $emprunt): ?>
                    <tr>
                        <td><?php echo $emprunt['id']; ?></td>
                        <td><?php echo htmlspecialchars($emprunt['utilisateur_prenom'] . ' ' . $emprunt['utilisateur_nom']); ?></td>
                        <td><?php echo htmlspecialchars($emprunt['livre_titre'] . ' (' . $emprunt['livre_auteur'] . ')'); ?></td>
                        <td><?php echo format_date($emprunt['date_emprunt']); ?></td>
                        <td><?php echo format_date($emprunt['date_retour_prevue']); ?></td>
                        <td><?php echo format_date($emprunt['date_retour']); ?></td>
                        <td>
                            <?php if ($emprunt['date_retour']): ?>
                                <span class="badge bg-success">Retourné</span>
                            <?php elseif (strtotime($emprunt['date_retour_prevue']) < time()): ?>
                                <span class="badge bg-danger">En retard</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">En cours</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$emprunt['date_retour']): ?>
                                <a href="index.php?page=retours&emprunt_id=<?php echo $emprunt['id']; ?>" class="btn btn-sm btn-primary">Retourner</a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-secondary" disabled>Retourné</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>