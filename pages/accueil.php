<div class="jumbotron">
    <h1 class="display-4">Bienvenue à la Bibliothèque</h1>
    <p class="lead">Explorez notre catalogue, empruntez des livres et gérez vos emprunts en ligne.</p>
    <hr class="my-4">
    <p>Notre bibliothèque dispose d'une large collection de livres dans diverses catégories.</p>

    <?php if (!isset($_SESSION['user_id'])): ?>
        <p>
            <a class="btn btn-primary btn-lg" href="index.php?page=login" role="button">Se connecter</a>
            <a class="btn btn-outline-primary btn-lg" href="index.php?page=register" role="button">S'inscrire</a>
        </p>
    <?php else: ?>
        <p>
            <a class="btn btn-primary btn-lg" href="index.php?page=livres" role="button">Consulter le catalogue</a>
        </p>
    <?php endif; ?>
</div>

<div class="row mt-5">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Catalogue en ligne</h5>
                <p class="card-text">Parcourez notre catalogue complet de livres. Recherchez par titre, auteur ou catégorie.</p>
                <a href="index.php?page=livres" class="btn btn-outline-primary">Accéder au catalogue</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Emprunter des livres</h5>
                <p class="card-text">Empruntez jusqu'à 5 livres simultanément pour une durée de 3 semaines.</p>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="index.php?page=livres" class="btn btn-outline-primary">Emprunter maintenant</a>
                <?php else: ?>
                    <a href="index.php?page=login" class="btn btn-outline-primary">Se connecter pour emprunter</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Gérer vos emprunts</h5>
                <p class="card-text">Consultez la liste de vos emprunts en cours et l'historique de vos lectures.</p>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="index.php?page=mes-emprunts" class="btn btn-outline-primary">Voir mes emprunts</a>
                <?php else: ?>
                    <a href="index.php?page=login" class="btn btn-outline-primary">Se connecter</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>