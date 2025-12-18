<?php

namespace App\Form;

use App\Entity\Participation;
use App\Entity\Oeuvre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class ParticipationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $artiste = $options['artiste'];
        
        $builder
            // Date de participation (readonly)
            ->add('dateparticipation', DateTimeType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'readonly' => true,
                ],
            ])
            // Statut du concours (disabled, valeur par défaut affichée)
            ->add('statut', TextType::class, [
                'label' => 'Statut',
                'data' => 'en_cours',
                'disabled' => true,
                'required' => true,
            ])
            // Vote public 
            ->add('votepublic', TextType::class, [
                'label' => 'Vote public',
                'data' => '👍 0',
                'disabled' => true,
            ])
            // Sélection de l'œuvre
            ->add('oeuvre', EntityType::class, [
                'class' => Oeuvre::class,
                'label' => 'Choisir une œuvre',
                'choice_label' => 'title',
                'placeholder' => 'Sélectionnez une œuvre',
                'required' => true,
                'query_builder' => function ($er) use ($artiste) {
                    return $er->createQueryBuilder('o')
                        ->where('o.artiste = :artiste')
                        ->setParameter('artiste', $artiste)
                        ->orderBy('o.title', 'ASC');
                },
                'attr' => [
                    'class' => 'form-select'
                ],
            ])
            // Description 
            ->add('description', TextareaType::class, [
                'label' => 'Description *',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Entrez votre description (au moins 10 caractères)...'
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participation::class,
            'artiste' => null,
            'attr'=>array('novalidate'=>'novalidate'),
        ]);
    }
}
