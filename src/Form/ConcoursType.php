<?php

namespace App\Form;

use App\Entity\Concours;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class ConcoursType extends AbstractType
{
    public function __construct(private UtilisateurRepository $utilisateurRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $today = (new \DateTime())->format('Y-m-d'); 
        $builder
            ->add('titre')
            ->add('description')
            ->add('jurys', EntityType::class, [
                'class' => Utilisateur::class,
                'choice_label' => fn (Utilisateur $user) => sprintf('%s (%s)', $user->getFullName(), $user->getEmail()),
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'query_builder' => fn () => $this->utilisateurRepository
                    ->createQueryBuilder('u')
                    ->where('u.role = :role')
                    ->setParameter('role', 'JURY')
                    ->orderBy('u.nom', 'ASC'),
                'help' => 'Sélectionnez les membres du jury pour ce concours.',
                'label' => 'Jurys',
            ])
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
           ->add('statut', ChoiceType::class, [
           'choices'  => [
           'Actif' => 'actif',
           'Clôturé' => 'cloture',
            ],
            'expanded' => false,
            'multiple' => false,
            'label' => 'Statut',
            'required' => true,
            'placeholder' => 'Sélectionnez un statut',
])

                
            ->add('regles')
            
        ->add('votePublic', ChoiceType::class, [
    'choices' => [
        'Oui' => true,
        'Non' => false,
    ],
    'expanded' => false,
    'multiple' => false,
    'label' => 'Vote public',
    'required' => true,
    'placeholder' => 'Sélectionnez vote public',
])
            
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
            'attr'=>array('novalidate'=>'novalidate'),
        ]);
    }
}
