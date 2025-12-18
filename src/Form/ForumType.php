<?php

namespace App\Form;

use App\Entity\Forum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ForumType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre')
            ->add('description')
            ->add('categorie', ChoiceType::class, [
                'choices' => [
                    'Art Général' => 'art_general',
                    'Peinture' => 'peinture',
                    'Photographie' => 'photographie',
                    'Sculpture' => 'sculpture',
                    'Graphisme Numérique' => 'graphisme',
                    'Concours' => 'concours',
                    'Autre' => 'autre',
                ],
                'placeholder' => 'Sélectionnez une catégorie',
                'required' => true,
            ])
        ;
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Forum::class,
            'attr'=>array('novalidate'=>'novalidate')
        ]);
    }
}