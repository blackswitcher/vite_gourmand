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

- service Docker : `db`
- port expose sur la machine : `3307`
- base : `vite_gourmand`

## Remarques

- le fichier `.env` sert a la configuration locale
- le fichier `.env.example` sert de modele
- en cas de probleme reseau avec Docker, tester temporairement avec un autre reseau

## Regle simple de configuration

- en local classique, `DB_HOST=127.0.0.1`
- avec Docker, `DB_HOST=db`

## Verification rapide

- site : `http://localhost:8080`
- base MySQL Docker : port `3307` depuis la machine