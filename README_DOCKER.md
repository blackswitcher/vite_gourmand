# Lancement du projet avec Docker

## Prerequis

- Docker Desktop installe et demarre
- WSL fonctionnel sur Windows

## Demarrer le projet

Depuis la racine du projet, lancer :

```powershell
docker compose up -d
```

Le site sera accessible a l'adresse :

```text
http://localhost:8080
```

Si c est le premier lancement, verifier que Docker Desktop est bien demarre avant d executer la commande.

## Verifier les conteneurs

Pour verifier que les conteneurs tournent :

```powershell
docker compose ps
```

## Arreter le projet

Pour arreter les conteneurs :

```powershell
docker compose down
```

## Reconstruire les conteneurs

Si des fichiers Docker ont ete modifies, reconstruire avec :

```powershell
docker compose up  --build
```

## Base de donnees

Le projet utilise MySQL dans Docker.

### Acces depuis l application Docker

Quand le site PHP parle a MySQL depuis Docker, il faut utiliser :

- `DB_HOST=db`
- `DB_PORT=3306`
- `DB_NAME=vite_gourmand`
- `DB_CHARSET=utf8mb4`
- `DB_USER=root`
- `DB_PASSWORD=root`

Ici, `db` est le nom du service Docker MySQL dans `docker-compose.yml`.



### Acces depuis Windows ou un outil externe

Quand on veut se connecter a MySQL depuis Windows, DBeaver, phpMyAdmin ou un autre outil externe, il faut utiliser :

- `127.0.0.1`
- port `3307`
- base : `vite_gourmand`
- utilisateur : `root`
- mot de passe : `root`

### Resume simple

- application Docker vers MySQL Docker : `db:3306`
- Windows vers MySQL Docker : `127.0.0.1:3307`

### Base utilisee

- base : `vite_gourmand`

## Remarques

- le fichier `.env` sert a la configuration locale
- le fichier `.env.example` sert de modele
- en cas de probleme reseau avec Docker, tester temporairement avec un autre reseau

## Verification rapide

- site : `http://localhost:8080`
- MySQL depuis Windows : `127.0.0.1:3307`
- MySQL depuis l application Docker : `db:3306`

## NoSQL

Le projet utilise aussi MongoDB pour journaliser certaines actions d administration.

Exemples d actions historisees :
- connexion admin ou employe
- modification de statut d une commande
- validation ou refus d un avis
- ajout ou suppression d un employe
- ajout ou suppression d un menu

## Securite et deploiement

### Points de securite deja en place

- utilisation de requetes preparees avec PDO
- mots de passe stockes sous forme de hash
- separation des acces selon les roles
- verification des actions admin depuis l espace d administration
- journalisation de certaines actions sensibles dans MongoDB

### Pistes de deploiement

Pour un deploiement reel, il faudrait notamment :

- des variables d environnement de production separees
- un serveur web configure proprement
- une base de donnees de production distincte
- une restriction des messages d erreur en production
- une gestion plus poussee des sauvegardes et des droits d acces