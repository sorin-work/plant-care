<?php

namespace App\Form;

use App\Entity\Household;
use App\Enum\Hemisphere;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Household>
 */
final class HouseholdSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Household name', 'empty_data' => ''])
            ->add('timezone', TimezoneType::class, [
                'help' => 'Used to decide when a day starts for your care tasks.',
            ])
            ->add('hemisphere', EnumType::class, [
                'class' => Hemisphere::class,
                'choice_label' => fn (Hemisphere $h): string => $h->label(),
                'help' => 'Seasons are flipped in the southern hemisphere.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Household::class]);
    }
}
