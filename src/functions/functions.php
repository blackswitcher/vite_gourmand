<?php

function getLibellesStatutsCommande(): array{
    return[
        0 => 'Accepté',
        1 => 'En preparation',
        2 => 'En cours de livraison',
        3 => 'Livré',
        4 => 'En attente du retour de materiel',
        5 => 'Terminée'
    ];
}