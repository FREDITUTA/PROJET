<?php
// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];

    // Vérification des identifiants
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['mot_de_passe'])) {
        // Création de la session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['nom'] . ' ' . $user['prenom'];
        $_SESSION['role'] = $user['role'];

        // Redirection
        $_SESSION['message'] = 'Connexion réussie. Bienvenue, ' . $_SESSION['username'] . '!';
        header('Location: index.php');
        exit;
    } else {
        $error = 'Identifiants incorrects. Veuillez réessayer.';
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h2 class="text-center">Connexion</h2>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Se connecter</button>
                    </div>
                </form>

                <div class="text-center mt-3">
                    <p>Pas encore de compte? <a href="index.php?page=register">S'inscrire</a></p>
                </div>
            </div>
        </div>
    </div>
</div>