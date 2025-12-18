<?php

namespace App\Form;

use App\Entity\Participation;
use App\Entity\Oeuvre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class ParticipationEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $artiste = $options['artiste'];
        
        $builder
            // Sélection de l'œuvre (image)
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
                        ->orderBy('o.title', 'ASC');  // ASC : trie les œuvres par titre par ordre alphabétique croissant.
                },
                'attr' => [
                    'class' => 'form-select'
                ],
            ])
            // Description
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Entrez votre description (au moins 10 caractères)...',
                    'rows' => 5 //attribut html pour  hauteur du textarea
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

