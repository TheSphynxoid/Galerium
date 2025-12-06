<?php

namespace App\Form;

use App\Entity\Participation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ParticipationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Date de participation (readonly)
            ->add('dateparticipation', DateTimeType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'readonly' => true, // visible mais non modifiable
                ],
            ])
            // Statut du concours (disabled, valeur par défaut affichée)
            ->add('statut', TextType::class, [
                'label' => 'Statut',
                'data' => 'en_cours',  // valeur affichée
                'disabled' => true,     // non modifiable
                'required' => true,
            ])
            // Vote public (affichage 👍 0, disabled)
            ->add('votepublic', TextType::class, [
                'label' => 'Vote public',
                'data' => '👍 0',
                'disabled' => true,
            ])
            // Description facultative
            ->add('description', TextType::class, [
                'label' => 'Description',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participation::class,
        ]);
    }
}
