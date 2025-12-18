<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;

class UtilisateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;
        
        $builder
            ->add('email', TextType::class, [
                'label' => 'Email',
                'attr' => [
                    'placeholder' => 'exemple@domaine.com',
                    'pattern' => '.*\\.com$',
                    'title' => 'L\'email doit se terminer par .com'
                ]
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'required' => !$isEdit,
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => $isEdit ? 'Laisser vide pour ne pas modifier' : ''
                ],
                'constraints' => $isEdit ? [] : [
                    new Assert\NotBlank(['message' => "Le mot de passe est obligatoire."]),
                    new Assert\Length([
                        'min' => 8,
                        'minMessage' => "Le mot de passe doit contenir au moins 8 caractères."
                    ])
                ]
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'maxlength' => 20,
                    'placeholder' => 'Nom (max 20)'
                ]
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'maxlength' => 20,
                    'placeholder' => 'Prénom (max 20)'
                ]
            ])

            ->add('telephone', TextType::class, [
                'label' => 'Téléphone'
            ]) 

            ->add('role', ChoiceType::class, [
                'label' => 'Rôle',
                'choices' => [
                    'Artiste' => 'ARTISTE',
                    'Visiteur' => 'VISITEUR',
                ],
                'expanded' => false,
                'multiple' => false,
            ])
            ->add('dateInscription', DateTimeType::class, [
                'label' => 'Date et heure d\'inscription',
                'widget' => 'single_text',
                'html5' => true,
            ])
        ;
    }
    
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
            'is_edit' => false,
        ]);
    }
}
