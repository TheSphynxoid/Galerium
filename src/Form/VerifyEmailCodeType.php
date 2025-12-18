<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class VerifyEmailCodeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code de vérification (5 chiffres)',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer le code de vérification']),
                    new Length([
                        'min' => 5,
                        'max' => 5,
                        'exactMessage' => 'Le code doit contenir exactement 5 chiffres',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'maxlength' => 5,
                    'pattern' => '\d{5}',
                    'title' => 'Veuillez entrer exactement 5 chiffres',
                    'autocomplete' => 'off',
                    'inputmode' => 'numeric',
                    'placeholder' => '12345'
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Vérifier le code',
                'attr' => ['class' => 'btn btn-primary']
            ])
        ;
    }
}
