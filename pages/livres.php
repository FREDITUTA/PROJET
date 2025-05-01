<?php
// Traitement de la recherche
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$category = isset($_GET['category']) ? clean_input($_GET['category']) : '';

// Récupération des catégories pour le filtre
$stmt = $pdo->query("SELECT DISTINCT categorie FROM livres ORDER BY categorie");
$categories = $stmt->fetchAll();

// Construction de la requête SQL avec recherche et filtre
$sql = "SELECT * FROM livres WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (titre LIKE ? OR auteur LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category)) {
    $sql .= " AND categorie = ?";
    $params[] = $category;
}

$sql .= " ORDER BY titre ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$livres = $stmt->fetchAll();

// Traitement du formulaire d'ajout de livre (admin seulement)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_book' && $_SESSION['role'] === 'admin') {
    $titre = clean_input($_POST['titre']);
    $auteur = clean_input($_POST['auteur']);
    $isbn = clean_input($_POST['isbn']);
    $categorie = clean_input($_POST['categorie']);
    $description = clean_input($_POST['description']);
    $quantite = (int) $_POST['quantite'];

    // Insertion du livre dans la base de données
    $stmt = $pdo->prepare("INSERT INTO livres (titre, auteur, isbn, categorie, description, quantite, emprunts_actifs) 
                           VALUES (?, ?, ?, ?, ?, ?, 0)");
    $success = $stmt->execute([$titre, $auteur, $isbn, $categorie, $description, $quantite]);

    if ($success) {
        $_SESSION['message'] = 'Le livre a été ajouté avec succès.';
        header('Location: index.php?page=livres');
        exit;
    }
}

// Traitement de l'emprunt d'un livre
if (isset($_POST['action']) && $_POST['action'] == 'emprunter' && isset($_SESSION['user_id'])) {
    $livre_id = (int) $_POST['livre_id'];

    // Vérifier si l'utilisateur peut emprunter plus de livres
    if (!can_borrow($pdo, $_SESSION['user_id'])) {
        $_SESSION['message'] = 'Vous avez atteint votre limite de 5 emprunts simultanés.';
        header('Location: index.php?page=livres');
        exit;
    }

    // Vérifier si le livre est disponible
    if (!is_livre_disponible($pdo, $livre_id)) {
        $_SESSION['message'] = 'Ce livre n\'est plus disponible pour l\'emprunt.';
        header('Location: index.php?page=livres');
        exit;
    }

    // Calculer la date de retour prévue (3 semaines)
    $date_retour_prevue = date('Y-m-d', strtotime('+21 days'));

    // Enregistrer l'emprunt
    $stmt = $pdo->prepare("INSERT INTO emprunts (utilisateur_id, livre_id, date_emprunt, date_retour_prevue) 
                          VALUES (?, ?, NOW(), ?)");
    $success = $stmt->execute([$_SESSION['user_id'], $livre_id, $date_retour_prevue]);

    if ($success) {
        // Mettre à jour le nombre d'emprunts actifs pour ce livre
        update_emprunts_actifs($pdo, $livre_id);

        $_SESSION['message'] = 'Livre emprunté avec succès. Date de retour prévue: ' . format_date($date_retour_prevue);
        header('Location: index.php?page=mes-emprunts');
        exit;
    }
}
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2>Catalogue des livres</h2>
    </div>
    <?php if ($_SESSION['role'] === 'admin'): ?>
        <div class="col-md-4 text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookModal">
                Ajouter un livre
            </button>
        </div>
    <?php endif; ?>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="index.php">
                    <input type="hidden" name="page" value="livres">
                    <div class="row">
                        <div class="col-md-5">
                            <input type="text" name="search" class="form-control" placeholder="Rechercher par titre ou auteur" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-4">
                            <select name="category" class="form-select">
                                <option value="">Toutes les catégories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['categorie']); ?>" <?php echo $category == $cat['categorie'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['categorie']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Rechercher</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <?php if (empty($livres)): ?>
        <div class="col-12">
            <div class="alert alert-info">Aucun livre ne correspond à votre recherche.</div>
        </div>
    <?php else: ?>
        <?php foreach ($livres as $livre): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($livre['titre']); ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($livre['auteur']); ?></h6>
                        <p class="card-text">
                            <small class="text-muted">
                                Catégorie: <?php echo htmlspecialchars($livre['categorie']); ?><br>
                                ISBN: <?php echo htmlspecialchars($livre['isbn']); ?>
                            </small>
                        </p>
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($livre['description'])); ?></p>
                        <p class="card-text">
                            <?php
                            $disponibles = $livre['quantite'] - $livre['emprunts_actifs'];
                            if ($disponibles > 0):
                            ?>
                                <span class="text-success">Disponible (<?php echo $disponibles; ?>)</span>
                            <?php else: ?>
                                <span class="text-danger">Indisponible</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="card-footer">
                        <?php if (isset($_SESSION['user_id']) && $disponibles > 0): ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="emprunter">
                                <input type="hidden" name="livre_id" value="<?php echo $livre['id']; ?>">
                                <button type="submit" class="btn btn-primary btn-sm">Emprunter</button>
                            </form>
                        <?php elseif (isset($_SESSION['user_id'])): ?>
                            <button class="btn btn-secondary btn-sm" disabled>Indisponible</button>
                        <?php else: ?>
                            <a href="index.php?page=login" class="btn btn-primary btn-sm">Se connecter pour emprunter</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal pour ajouter un livre (admin seulement) -->
<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <div class="modal fade" id="addBookModal" tabindex="-1" aria-labelledby="addBookModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBookModalLabel">Ajouter un livre</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add_book">
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
                            <label for="categorie" class="form-label">Catégorie</label>
                            <input type="text" class="form-control" id="categorie" name="categorie" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="quantite" class="form-label">Quantité</label>
                            <input type="number" class="form-control" id="quantite" name="quantite" min="1" value="1" required>
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
<?php endif; ?>