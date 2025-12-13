<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */

//   tu peux l’utiliser sur une propriété  //ou une méthode // tu peux répéter plusieurs fois la contrainte si nécessaire
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)] 
class BadWords extends Constraint
{
    public string $message = 'Le texte contient des mots inappropriés : {{ words }}. Veuillez modifier votre description.';
    
    public function validatedBy(): string
    {
        return BadWordsValidator::class;
    }
}


