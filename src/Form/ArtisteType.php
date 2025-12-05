<?php

namespace App\Form;

use App\Entity\Artiste;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ArtisteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
                ->add('displayName', TextType::class, [
                    'label' => 'Nom d\'artiste',
                    'required' => true,
                ])
            ->add('specialty', TextType::class, [
                'required' => true,  // Changé à true pour afficher les erreurs
            ])
            ->add('biography', TextareaType::class, [
                'required' => true,  // Changé à true pour afficher les erreurs
            ])
            ->add('website', UrlType::class, [
                'required' => true,  // Changé à true pour afficher les erreurs
            ])
            ->add('facebook', UrlType::class, [
                'required' => false,
            ])
            ->add('instagram', UrlType::class, [
                'required' => false,
            ])
            ->add('behance', UrlType::class, [
                'required' => false,
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Avatar',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => true,
                'image_uri' => true,
                'asset_helper' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Artiste::class,
        ]);
    }
    
}