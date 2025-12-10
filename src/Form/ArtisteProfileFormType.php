<?php

namespace App\Form;

use App\Entity\Artiste;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ArtisteProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('displayName', TextType::class, [
                'label' => 'Nom d\'artiste',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('specialty', TextType::class, [
                'label' => 'Spécialité',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('biography', TextareaType::class, [
                'label' => 'Biographie',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 5]
            ])
            ->add('website', TextType::class, [
                'label' => 'Site web',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://exemple.com'
                ]
            ])
            ->add('facebook', TextType::class, [
                'label' => 'Facebook',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://facebook.com/...'
                ]
            ])
            ->add('instagram', TextType::class, [
                'label' => 'Instagram',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://instagram.com/...'
                ]
            ])
            ->add('behance', TextType::class, [
                'label' => 'Behance',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://behance.net/...'
                ]
            ])
            ->add('imageFile', \Vich\UploaderBundle\Form\Type\VichImageType::class, [
                'label' => 'Photo de profil',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => true,
                'image_uri' => true,
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Artiste::class,
        ]);
    }
}







