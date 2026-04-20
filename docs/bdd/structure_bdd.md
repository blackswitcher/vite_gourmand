# Structure de la base de donnees - Vite & Gourmand

## Tables

- avis
- commande
- commande_items
- galerie_menu
- menu_produit
- menu_regime
- menus
- produits
- regimes
- themes
- users

## Role des tables

### users

Stocke les comptes utilisateurs, employes et administrateurs.

Champs importants :

- ID
- nom
- prenom
- email
- telephone
- ville
- rue
- code_postal
- password_hash
- created_at
- role

### menus

Stocke les menus proposes par l entreprise.

Champs importants :

- ID
- titre
- description
- prix
- nb_personne
- img_cover
- actif
- created_at
- theme_id

### produits

Stocke les produits ou plats qui peuvent composer un menu.

Champs importants :

- ID
- nom
- description
- actif
- type
- date_creation

### themes

Stocke les themes des menus.

Exemples :

- Noel
- Paques
- classique
- evenement

### regimes

Stocke les regimes alimentaires.

Exemples :

- classique
- vegetarien
- vegan
- sans gluten

### commande

Stocke les commandes passees par les utilisateurs.

Champs importants :

- ID
- user_id
- date_creation
- statut
- total

### commande_items

Table de liaison entre une commande et les menus commandes.

Champs importants :

- menu_id
- commande_id
- quantite
- prix_unitaire

### avis

Stocke les avis clients.

Champs importants :

- ID
- user_id
- menu_id
- note
- commentaire
- date_creation
- statut

### galerie_menu

Stocke les images associees a un menu.

Champs importants :

- ID
- img_path
- alt
- ordre
- menu_id

### menu_produit

Table de liaison entre les menus et les produits.

### menu_regime

Table de liaison entre les menus et les regimes.

## Points a verifier

- Ajouter ou verifier les cles etrangeres.
- Verifier que les roles utilisateurs sont coherents : utilisateur, employe, admin.
- Verifier que les mots de passe sont stockes avec password_hash.
- Verifier que les avis ont un statut pour moderation.
- Verifier que les commandes ont un statut pour le suivi.
