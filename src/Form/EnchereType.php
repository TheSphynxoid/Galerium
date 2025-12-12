<?php

namespace App\Form;

use App\Entity\Enchere;
use App\Entity\Oeuvre;
use App\Enum\EnchereStatut;
use App\Repository\OeuvreRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnchereType extends AbstractType
{
    public function __construct(private OeuvreRepository $oeuvreRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Oeuvre', EntityType::class, [
                'class' => Oeuvre::class,
                'choice_label' => 'title',
                'query_builder' => fn() => $this->oeuvreRepository->findNotInEnchere(),
            ])
            ->add('prixDeBase',NumberType::class, [
                "required" => false,
            ])
            ->add('dateFin');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Enchere::class,
        ]);
    }
}
