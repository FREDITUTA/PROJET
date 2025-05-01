<?php
// Page accessible uniquement aux administrateurs
if ($_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Traitement de l'ajout d'une commande
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_commande') {
    $titre = clean_input($_POST['titre']);
    $auteur = clean_input($_POST['auteur']);
    $isbn = clean_input($_POST['isbn']);
    $quantite = (int) $_POST['quantite'];
    $notes = clean_input($_POST['notes']);

    // Insertion de la commande
    $stmt = $pdo->prepare("INSERT INTO commandes (titre, auteur, isbn, quantite, date_commande, statut, notes) 
                          VALUES (?, ?, ?, ?, NOW(), 'en_attente', ?)");
    $success = $stmt->execute([$titre, $auteur, $isbn, $quantite, $notes]);

    if ($success) {
        $_SESSION['message'] = 'La commande a été ajoutée avec succès.';
        header('Location: index.php?page=commandes');
        exit;
    }
}

// Traitement de la mise à jour du statut d'une commande
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $commande_id = (int) $_POST['commande_id'];
    $statut = clean_input($_POST['statut']);

    $stmt = $pdo->prepare("UPDATE commandes SET statut = ? WHERE id = ?");
    $success = $stmt->execute([$statut, $commande_id]);

    if ($success) {
        // Si la commande est reçue, on ajoute les livres à l'inventaire
        if ($statut === 'recue') {
            $stmt = $pdo->prepare("SELECT * FROM commandes WHERE id = ?");
            $stmt->execute([$commande_id]);
            $commande = $stmt->fetch();

            if ($commande) {
                // Vérifier si le livre existe déjà dans l'inventaire
                $stmt = $pdo->prepare("SELECT id, quantite FROM livres WHERE isbn = ?");
                $stmt->execute([$commande['isbn']]);
                $livre = $stmt->fetch();

                if ($livre) {
                    // Mettre à jour la quantité du livre existant
                    $stmt = $pdo->prepare("UPDATE livres SET quantite = quantite + ? WHERE id = ?");
                    $stmt->execute([$commande['quantite'], $livre['id']]);
                } else {
                    // Ajouter un nouveau livre à l'inventaire
                    $stmt = $pdo->prepare("INSERT INTO livres (titre, auteur, isbn, categorie, quantite, emprunts_actifs) 
                                           VALUES (?, ?, ?, 'Non classé', ?, 0)");
                    $stmt->execute([$commande['titre'], $commande['auteur'], $commande['isbn'], $commande['quantite']]);
                }

                // Mettre à jour la date de réception
                $stmt = $pdo->prepare("UPDATE commandes SET date_reception = NOW() WHERE id = ?");
                $stmt->execute([$commande_id]);
            }
        }

        $_SESSION['message'] = 'Le statut de la commande a été mis à jour.';
        header('Location: index.php?page=commandes');
        exit;
    }
}

// Filtrage des commandes
$status = isset($_GET['status']) ? clean_input($_GET['status']) : 'all';

// Construction de la requête SQL
$sql = "SELECT * FROM commandes WHERE 1=1";
$params = [];

if ($status !== 'all') {
    $sql .= " AND statut = ?";
    $params[] = $status;
}

$sql .= " ORDER BY date_commande DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$commandes = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2>Gestion des commandes</h2>
    </div>
    <div class="col-md-4 text-end">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCommandeModal">
            Nouvelle commande
        </button>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="index.php">
                    <input type="hidden" name="page" value="commandes">
                    <div class="row">
                        <div class="col-md-9">
                            <select name="status" class="form-select">
                                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>Toutes les commandes</option>
                                <option value="en_attente" <?php echo $status === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                <option value="commandee" <?php echo $status === 'commandee' ? 'selected' : ''; ?>>Commandée</option>
                                <option value="expedie" <?php echo $status === 'expedie' ? 'selected' : ''; ?>>Expédiée</option>
                                <option value="recue" <?php echo $status === 'recue' ? 'selected' : ''; ?>>Reçue</option>
                                <option value="annulee" <?php echo $status === 'annulee' ? 'selected' : ''; ?>>Annulée</option>
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
                <th>Titre</th>
                <th>Auteur</th>
                <th>ISBN</th>
                <th>Quantité</th>
                <th>Date de commande</th>
                <th>Date de réception</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($commandes)): ?>
                <tr>
                    <td colspan="9" class="text-center">Aucune commande trouvée.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($commandes as $commande): ?>
                    <tr>
                        <td><?php echo $commande['id']; ?></td>
                        <td><?php echo htmlspecialchars($commande['titre']); ?></td>
                        <td><?php echo htmlspecialchars($commande['auteur']); ?></td>
                        <td><?php echo htmlspecialchars($commande['isbn']); ?></td>
                        <td><?php echo $commande['quantite']; ?></td>
                        <td><?php echo format_date($commande['date_commande']); ?></td>
                        <td><?php echo format_date($commande['date_reception']); ?></td>
                        <td>
                            <?php
                            $badge_class = '';
                            switch ($commande['statut']) {
                                case 'en_attente':
                                    $badge_class = 'bg-secondary';
                                    break;
                                case 'commandee':
                                    $badge_class = 'bg-primary';
                                    break;
                                case 'expedie':
                                    $badge_class = 'bg-info';
                                    break;
                                case 'recue':
                                    $badge_class = 'bg-success';
                                    break;
                                case 'annulee':
                                    $badge_class = 'bg-danger';
                                    break;
                            }
                            ?>
                            <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($commande['statut']); ?></span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                    Modifier statut
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="commande_id" value="<?php echo $commande['id']; ?>">
                                            <input type="hidden" name="statut" value="en_attente">
                                            <button type="submit" class="dropdown-item">En attente</button>
                                        </form>
                                    </li>
                                    <li>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="commande_id" value="<?php echo $commande['id']; ?>">
                                            <input type="hidden" name="statut" value="commandee">
                                            <button type="submit" class="dropdown-item">Commandée</button>
                                        </form>
                                    </li>
                                    <li>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="commande_id" value="<?php echo $commande['id']; ?>">
                                            <input type="hidden" name="statut" value="expedie">
                                            <button type="submit" class="dropdown-item">Expédiée</button>
                                        </form>
                                    </li>
                                    <li>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="commande_id" value="<?php echo $commande['id']; ?>">
                                            <input type="hidden" name="statut" value="recue">
                                            <button type="submit" class="dropdown-item">Reçue</button>
                                        </form>
                                    </li>
                                    <li>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="commande_id" value="<?php echo $commande['id']; ?>">
                                            <input type="hidden" name="statut" value="annulee">
                                            <button type="submit" class="dropdown-item">Annulée</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal pour ajouter une commande -->
<div class="modal fade" id="addCommandeModal" tabindex="-1" aria-labelledby="addCommandeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCommandeModalLabel">Nouvelle commande</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_commande">
                    <div class="mb-3">
                        <label for="titre" class="form-label">Titre</label>
                        <input type="text" class="form-control" id="titre" name="titre" required>
                    </div>
                    <div class="mb-3">
                        <label for="auteur" class="form-label">Auteur</label>
                        <input type="text" class="form-control" id="auteur" name="auteur" required>
                    </div>
                    <div class="mb-3">
                        <label for="isbn" class="form-label">ISBN</label>
                        <input type="text" class="form-control" id="isbn" name="isbn" required>
                    </div>
                    <div class="mb-3">
                        <label for="quantite" class="form-label">Quantité</label>
                        <input type="number" class="form-control" id="quantite" name="quantite" min="1" value="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>