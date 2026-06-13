# Vite & Gourmand - ECF DWWM

Projet realise dans le cadre de l ECF Developpeur Web et Web Mobile.

## Objectif du projet

Vite & Gourmand est une entreprise de traiteur situee a Bordeaux.

L objectif de l application est de permettre aux visiteurs de consulter les menus proposes, de creer un compte, de commander un menu, puis de suivre leurs commandes.

L application doit aussi permettre aux employes et a l administrateur de gerer les menus, les commandes et les avis clients.

## Stack technique

- PHP
- MySQL
- PDO
- HTML
- CSS
- JavaScript

## Base de donnees

La structure de la base de donnees est documentee dans :

- `docs/bdd/2026-04-20_vite_gourmand.docx`
- `docs/bdd/structure_bdd.md`

Le projet utilise MySQL avec Docker.

## Installation et lancement

### Lancement rapide

1. Ouvrir un terminal a la racine du projet
2. Lancer la commande :

```powershell
docker compose up -d

3. ouvrir le site dans le navigateur 
http://localhost:8080

## verification des conteneurs

utilise dans le terminal la commande 
docker compose ps 

### Configuration importante 

depuis l application Docker 
-`DB_HOST=db`
-`DB_POST=3306`

Depuis Windows ou un outil externe
-``127.0.0.1:3007

### Fichiers utiles

-script SQL principal: `database/vite_gourmand.sql`
-exemple d environement: `.env.example`

## Fonctionnalites principales

-consultation des menus
- detail d un menu 
- ajout au panier 
- gestion des commandes 
- gestion des avis
- espace admin
- gestion des employes
- gestion des menus 
- journalisation des actions admin dans MongoDB 

## Documentation disponible 

-Docker: `README_DOCKER.md`
-script SQL principal : `database/vite_gourmand.sql`
- structure de BDD : `docs/bdd/structure_bdd.md`
- documentation BDD complementaire : `docs/bdd/2026-04-20_vite_gourmand.docx`




