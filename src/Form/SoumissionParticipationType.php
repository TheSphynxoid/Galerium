<?php

namespace App\Form;

use App\Entity\Oeuvre;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SoumissionParticipationType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('oeuvre', EntityType::class, [
				'class' => Oeuvre::class,
				'choices' => $options['oeuvres'],
				'choice_label' => 'titre',
				'label' => 'Choisissez une œuvre à soumettre',
				'placeholder' => 'Sélectionner une œuvre',
				'required' => true,
				'attr' => ['class' => 'form-select']
			]);
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'oeuvres' => [],
		]);
	}
}





