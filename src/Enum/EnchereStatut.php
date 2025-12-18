<?php

namespace App\Enum;

enum EnchereStatut: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case TERMINEE = 'terminee';
    case ANNULEE = 'annulee';

    
}