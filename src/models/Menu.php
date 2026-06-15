<?php 

class Menu
{
    // ID du menu en base SQL 
    public int $id;

    //titre du menu 
    public string $titre;

    // description du menu 
    public string $description;

    //prix du menu 
    public float $prix;

    //nombre minimum de personne
    public int $nbPersonne;

    // image de chover pour l'instant uniquement chemin du projet a modifier 
    public string $imgCover;

    //staut actif ou non 
    public int $actif;

    // theme rattacher au menu 
    public ?int $themeId;


    public function __construct(
        int $id,
        string $titre,
        string $description,
        float $prix,
        int $nbPersonne,
        string $imgCover,
        int $actif,
        ?int $themeId
    )
    {
        $this->id = $id;
        $this->titre = $titre;
        $this->description = $description;
        $this->prix = $prix;
        $this->nbPersonne = $nbPersonne;
        $this->imgCover = $imgCover;
        $this->actif = $actif;
        $this->themeId = $themeId;
    }
        // Ojet Menu a partir d'une ligne SQL en BDD 
    //facilite l'utilisation de la classe 

    public static function fromDatabaseRow(array $row): self{
        return new self(
            (int) $row['ID'],
            (string) $row['titre'],
            (string) $row['description'],
            (float) $row['prix'],
            (int) $row['nb_personne'],
            (string) $row['img_cover'],
            1,
            null
        );
        }
    

    public function getPrixFormate(): string
    {
        return number_format($this->prix, 2, ',', ' ') . 'EUR';
    }

    // le but de la methode est de nettoyer le chemin de l'image dans le navigateur
    public function getImagePath(): string
    {
        return str_replace('\\', '/',$this->imgCover);
    }

    public function getMinimumPersonnesTexte(): string
    {
        return $this->nbPersonne . ' Personnes';
    }


    }
