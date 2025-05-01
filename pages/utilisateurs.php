<?php
// Page accessible uniquement aux administrateurs
if ($_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Traitement du formulaire pour changer le rôle d'un utilisateur
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'change_role') {
    $user_id = (int) $_POST['user_id'];
    $role = clean_input($_POST['role']);

    // Mettre à jour le rôle de l'utilisateur
    $stmt = $pdo->prepare("UPDATE utilisateurs SET role = ? WHERE id = ?");
    $success = $stmt->execute([$role, $user_id]);

    if ($success) {
        $_SESSION['message'] = 'Le rôle de l\'utilisateur a été mis à jour.';
        header('Location: index.php?page=utilisateurs');
        exit;
    }
}

// Récupération des utilisateurs
$sql = "SELECT * FROM utilisateurs ORDER BY nom, prenom";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$utilisateurs = $stmt->fetchAll();
?>

<h2>Gestion des utilisateurs</h2>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Date d'inscription</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($utilisateurs)): ?>
                <tr>
                    <td colspan="7" class="text-center">Aucun utilisateur trouvé.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($utilisateurs as $utilisateur): ?>
                    <tr>
                        <td><?php echo $utilisateur['id']; ?></td>
                        <td><?php echo htmlspecialchars($utilisateur['nom']); ?></td>
                        <td><?php echo htmlspecialchars($utilisateur['prenom']); ?></td>
                        <td><?php echo htmlspecialchars($utilisateur['email']); ?></td>
                        <td>
                            <span class="badge <?php echo $utilisateur['role'] === 'admin' ? 'bg-danger' : 'bg-info'; ?>">
                                <?php echo ucfirst($utilisateur['role']); ?>
                            </span>
                        </td>
                        <td><?php echo format_date($utilisateur['date_inscription']); ?></td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                    Changer rôle
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="change_role">
                                            <input type="hidden" name="user_id" value="<?php echo $utilisateur['id']; ?>">
                                            <input type="hidden" name="role" value="membre">
                                            <button type="submit" class="dropdown-item" <?php echo $utilisateur['role'] === 'membre' ? 'disabled' : ''; ?>>Membre</button>
                                        </form>
                                    </li>
                                    <li>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="change_role">
                                            <input type="hidden" name="user_id" value="<?php echo $utilisateur['id']; ?>">
                                            <input type="hidden" name="role" value="admin">
                                            <button type="submit" class="dropdown-item" <?php echo $utilisateur['role'] === 'admin' ? 'disabled' : ''; ?>>Administrateur</button>
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