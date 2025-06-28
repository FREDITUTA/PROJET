-- Structure de la base de données pour la gestion de bibliothèque

-- Base de données : `bibliotheque`
CREATE DATABASE IF NOT EXISTS `bibliotheque` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `bibliotheque`;

-- Table `utilisateurs`
CREATE TABLE `utilisateurs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` enum('membre','admin') NOT NULL DEFAULT 'membre',
  `date_inscription` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `livres`
CREATE TABLE `livres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `auteur` varchar(255) NOT NULL,
  `isbn` varchar(20) NOT NULL,
  `categorie` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  `emprunts_actifs` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `isbn` (`isbn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `emprunts`
CREATE TABLE `emprunts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(11) NOT NULL,
  `livre_id` int(11) NOT NULL,
  `date_emprunt` datetime NOT NULL,
  `date_retour_prevue` date NOT NULL,
  `date_retour` datetime DEFAULT NULL,
  `frais_retard` decimal(5,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`),
  KEY `livre_id` (`livre_id`),
  CONSTRAINT `emprunts_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `emprunts_ibfk_2` FOREIGN KEY (`livre_id`) REFERENCES `livres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `commandes`
CREATE TABLE `commandes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `auteur` varchar(255) NOT NULL,
  `isbn` varchar(20) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  `date_commande` datetime NOT NULL,
  `date_reception` datetime DEFAULT NULL,
  `statut` enum('en_attente','commandee','expedie','recue','annulee') NOT NULL DEFAULT 'en_attente',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Création d'un compte administrateur par défaut (mot de passe: admin123)
INSERT INTO `utilisateurs` (`nom`, `prenom`, `email`, `mot_de_passe`, `role`, `date_inscription`) 
VALUES ('Admin', 'Système', 'admin@bibliotheque.com', '$2y$10$XFZhPq0LoL.xqofK7B5Z9.YB0MBkd0UVmQ9Mk8Hl4A8ueIaMuNuuq', 'admin', NOW());

-- Ajout de quelques livres d'exemple
INSERT INTO `livres` (`titre`, `auteur`, `isbn`, `categorie`, `description`, `quantite`, `emprunts_actifs`) VALUES
('Le Petit Prince', 'Antoine de Saint-Exupéry', '9782070612758', 'Roman', 'Un livre poétique qui aborde les thèmes de l\'amour, l\'amitié, et le sens de la vie.', 3, 0),
('1984', 'George Orwell', '9782070368228', 'Science-fiction', 'Un roman dystopique qui décrit un futur où la population est soumise à une surveillance permanente.', 2, 0),
('Les Misérables', 'Victor Hugo', '9782253096344', 'Roman classique', 'L\'histoire de Jean Valjean, un ancien forçat qui tente de se racheter.', 1, 0),
('Harry Potter à l\'école des sorciers', 'J.K. Rowling', '9782070643028', 'Fantasy', 'Le premier tome des aventures du jeune sorcier Harry Potter.', 5, 0),
('Le Seigneur des Anneaux : La Communauté de l\'Anneau', 'J.R.R. Tolkien', '9782070612888', 'Fantasy', 'Premier tome d\'une trilogie épique dans un monde fantastique.', 2, 0);