-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Hà´te : localhost:3307
-- Généré le : lun. 20 avr. 2026 à 18:04
-- Version du serveur : 5.7.24
-- Version de PHP : 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `vite_gourmand`
--

-- --------------------------------------------------------

--
-- Structure de la table `avis`
--

CREATE TABLE `avis` (
  `ID` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `commande_id` int(10) UNSIGNED NOT NULL,
  `note` tinyint(3) UNSIGNED NOT NULL,
  `commentaire` text NOT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `statut` tinyint(3) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `commande`
--

CREATE TABLE `commande` (
  `ID` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `statut` tinyint(3) UNSIGNED NOT NULL,
  `total` decimal(8,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `commande_items`
--

CREATE TABLE `commande_items` (
  `menu_id` int(10) UNSIGNED NOT NULL,
  `commande_id` int(10) UNSIGNED NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT '1',
  `prix_unitaire` decimal(8,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `galerie_menu`
--

CREATE TABLE `galerie_menu` (
  `ID` int(10) UNSIGNED NOT NULL,
  `img_path` varchar(255) NOT NULL,
  `alt` text NOT NULL,
  `ordre` int(11) NOT NULL,
  `menu_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `menus`
--

CREATE TABLE `menus` (
  `ID` int(10) UNSIGNED NOT NULL,
  `titre` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `prix` decimal(6,2) NOT NULL,
  `nb_personne` int(11) NOT NULL,
  `img_cover` varchar(255) NOT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `theme_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `menus`
--

INSERT INTO `menus` (`ID`, `titre`, `description`, `prix`, `nb_personne`, `img_cover`, `actif`, `created_at`, `theme_id`) VALUES
(1, 'Tradition', 'Un menu traditionnel et généreux,idéal pour les repas familiaux et événements simples', '120.00', 4, 'assetIMGcover_menucover_menu_classique.png', 1, '2026-01-14 14:26:07', NULL),
(2, 'Festif Noel', 'Un menu raffiné aux saveurs de fêtes, parfait pour les repas de fin dâ€™année.', '210.00', 6, 'assetIMGcover_menucover_menu_noel.png', 1, '2026-01-14 14:26:07', NULL),
(3, 'Formule végétarien à‰quilibé', 'Un menu sain et savoureux, sans viande, mettant en valeur des produits frais.', '75.00', 3, 'assetIMGcover_menucover_menu_VG.png', 1, '2026-01-14 14:26:07', NULL),
(4, 'Formule Végan créatif', 'Une expérience culinaire 100% végétale, moderne et gourmande.', '120.00', 4, 'assetIMGcover_menucover_vegan.png', 1, '2026-01-14 14:26:07', NULL),
(5, 'Formule sans gluten', 'Un menu conà§u pour les personnes intolérantes au gluten, avec des précautions strictes.', '70.00', 2, 'assetIMGcover_menucover_SSGluten.png', 1, '2026-01-14 14:26:07', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `menu_produit`
--

CREATE TABLE `menu_produit` (
  `ID` int(10) UNSIGNED NOT NULL,
  `produit_id` int(10) UNSIGNED NOT NULL,
  `menu_id` int(10) UNSIGNED NOT NULL,
  `quantité` int(11) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `menu_regime`
--

CREATE TABLE `menu_regime` (
  `menu_id` int(10) UNSIGNED NOT NULL,
  `regimes_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `produits`
--

CREATE TABLE `produits` (
  `ID` int(10) UNSIGNED NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `actif` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `type` enum('viennoiserie','patisserie','boisson') NOT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `regimes`
--

CREATE TABLE `regimes` (
  `regimes_id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(255) DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `regimes`
--

INSERT INTO `regimes` (`regimes_id`, `nom`, `date_creation`) VALUES
(1, 'sans gluten', '2026-01-14 11:16:32'),
(2, 'vegetarien', '2026-01-14 11:19:53'),
(3, 'vegan', '2026-01-14 11:19:53');

-- --------------------------------------------------------

--
-- Structure de la table `themes`
--

CREATE TABLE `themes` (
  `ID` int(10) UNSIGNED NOT NULL,
  `nom` varchar(255) NOT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `themes`
--

INSERT INTO `themes` (`ID`, `nom`, `date_creation`) VALUES
(1, 'Classique', '2026-01-19 12:08:25'),
(2, 'Noel', '2026-01-19 12:08:25'),
(3, 'à‰vénements', '2026-01-19 12:08:25'),
(4, 'Pà¢ques', '2026-01-19 12:08:25');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `ID` int(10) UNSIGNED NOT NULL,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telephone` varchar(10) DEFAULT NULL,
  `ville` varchar(255) NOT NULL,
  `rue` varchar(255) NOT NULL,
  `code_postal` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `role` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`ID`, `nom`, `prenom`, `email`, `telephone`, `ville`, `rue`, `code_postal`, `password_hash`, `created_at`, `role`) VALUES
(1, 'Dupont', 'Jean', 'jean.dupont@gmail.com', '0612345684', 'Nice', '12 rue des Oliviers', '06000', '$2y$10$eOUD1LGPiMXYTmZv2Ez8m.fhgOCrdAK4NDdjdanYNY8iKm1/9e.Xq', '2026-01-14 11:13:40', 'client'),
(2, 'edward', 'Elric', 'edward.elric@yahoo.fr', '0615243658', 'nice', '125 rue des magnolia', '06200', '$2y$10$eKMH04gmSNyUdEQ.9op5jO6acB8ckPpM8TLcg97stlL53.PGBcmd6', '2026-04-07 15:12:49', 'client');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `avis`
--
ALTER TABLE `avis`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `commande_id` (`commande_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `commande`
--
ALTER TABLE `commande`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `commande_items`
--
ALTER TABLE `commande_items`
  ADD PRIMARY KEY (`commande_id`,`menu_id`),
  ADD KEY `menu_id` (`menu_id`);

--
-- Index pour la table `galerie_menu`
--
ALTER TABLE `galerie_menu`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `menu_id` (`menu_id`);

--
-- Index pour la table `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `fk_menu_themes` (`theme_id`);

--
-- Index pour la table `menu_produit`
--
ALTER TABLE `menu_produit`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `produit_id` (`produit_id`),
  ADD KEY `menu_id` (`menu_id`);

--
-- Index pour la table `menu_regime`
--
ALTER TABLE `menu_regime`
  ADD PRIMARY KEY (`menu_id`,`regimes_id`),
  ADD KEY `regimes_id` (`regimes_id`);

--
-- Index pour la table `produits`
--
ALTER TABLE `produits`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `nom` (`nom`);

--
-- Index pour la table `regimes`
--
ALTER TABLE `regimes`
  ADD PRIMARY KEY (`regimes_id`);

--
-- Index pour la table `themes`
--
ALTER TABLE `themes`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `nom` (`nom`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `avis`
--
ALTER TABLE `avis`
  MODIFY `ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `commande`
--
ALTER TABLE `commande`
  MODIFY `ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `galerie_menu`
--
ALTER TABLE `galerie_menu`
  MODIFY `ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `menus`
--
ALTER TABLE `menus`
  MODIFY `ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `menu_produit`
--
ALTER TABLE `menu_produit`
  MODIFY `ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `produits`
--
ALTER TABLE `produits`
  MODIFY `ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `regimes`
--
ALTER TABLE `regimes`
  MODIFY `regimes_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `themes`
--
ALTER TABLE `themes`
  MODIFY `ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `avis`
--
ALTER TABLE `avis`
  ADD CONSTRAINT `avis_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `avis_ibfk_2` FOREIGN KEY (`commande_id`) REFERENCES `commande` (`ID`) ON DELETE CASCADE;

--
-- Contraintes pour la table `commande`
--
ALTER TABLE `commande`
  ADD CONSTRAINT `commande_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`ID`) ON DELETE CASCADE;

--
-- Contraintes pour la table `commande_items`
--
ALTER TABLE `commande_items`
  ADD CONSTRAINT `commande_items_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `commande_items_ibfk_2` FOREIGN KEY (`commande_id`) REFERENCES `commande` (`ID`) ON DELETE CASCADE;

--
-- Contraintes pour la table `galerie_menu`
--
ALTER TABLE `galerie_menu`
  ADD CONSTRAINT `galerie_menu_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`ID`) ON DELETE CASCADE;

--
-- Contraintes pour la table `menus`
--
ALTER TABLE `menus`
  ADD CONSTRAINT `fk_menu_themes` FOREIGN KEY (`theme_id`) REFERENCES `themes` (`ID`) ON DELETE SET NULL;

--
-- Contraintes pour la table `menu_produit`
--
ALTER TABLE `menu_produit`
  ADD CONSTRAINT `menu_produit_ibfk_1` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `menu_produit_ibfk_2` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`ID`) ON DELETE CASCADE;

--
-- Contraintes pour la table `menu_regime`
--
ALTER TABLE `menu_regime`
  ADD CONSTRAINT `menu_regime_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `menu_regime_ibfk_2` FOREIGN KEY (`regimes_id`) REFERENCES `regimes` (`regimes_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


