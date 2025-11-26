<?php

namespace App\Form;

use App\Entity\Enchere;
use App\Enum\EnchereStatut;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnchereType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prixDeBase',NumberType::class, [
                "required" => false,
            ])
            ->add('dateFin')
            ->add('statut', EnumType::class, [
    'class' => EnchereStatut::class,
    'choice_label' => fn ($choice) => $choice->value, // optional
]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Enchere::class,
        ]);
    }
}
