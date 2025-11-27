<?php

namespace App\Form;

use App\Entity\Concours;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class ConcoursType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $today = (new \DateTime())->format('Y-m-d'); // date du jour

        $builder
            ->add('titre')
            ->add('description')
            ->add('dateDebut', DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'min' => $today,
                    'max' => '2026-12-31',
                ],
                'html5' => true,
            ])
            ->add('dateFin', DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'min' => $today,
                    'max' => '2026-12-31',
                ],
                'html5' => true,
            ])
            ->add('statut')
            ->add('regles')
            ->add('votePublic')
            ->add('dateDebutVote', DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'min' => $today,
                    'max' => '2026-12-31',
                ],
                'html5' => true,
            ])
            ->add('dateFinVote', DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'min' => $today,
                    'max' => '2026-12-31',
                ],
                'html5' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Concours::class,
        ]);
    }
}
