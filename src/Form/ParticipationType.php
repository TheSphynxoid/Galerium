<?php

namespace App\Form;

use App\Entity\Participation;
use App\Entity\Oeuvre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ParticipationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('oeuvre', EntityType::class, [
                'class' => Oeuvre::class,
                'choice_label' => function (Oeuvre $oeuvre) {
                    return $oeuvre->getTitle();
                },
                'choices' => $options['oeuvres'] ?? [],
                'placeholder' => 'Choisissez une œuvre',
                'required' => true,
                'label' => 'Votre œuvre',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description de votre participation',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5
                ]
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'En cours' => 'En cours',
                    'Soumis' => 'Soumis',
                    'En attente' => 'En attente'
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participation::class,
            'oeuvres' => [],
        ]);
    }
}
