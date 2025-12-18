<?php

namespace App\Validator\Constraints;

use App\Service\BadWordsService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class BadWordsValidator extends ConstraintValidator
{
    private BadWordsService $badWordsService;

    public function __construct(BadWordsService $badWordsService)
    {
        $this->badWordsService = $badWordsService;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof BadWords) {
            throw new UnexpectedTypeException($constraint, BadWords::class);
        }

        // Si la valeur est vide, on laisse les autres contraintes gérer (NotBlank, etc.)
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        // Vérifier les mots inappropriés
        $foundWords = $this->badWordsService->checkBadWords($value);

        if (!empty($foundWords)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ words }}', implode(', ', $foundWords))
                ->addViolation();
        }
    }
}




