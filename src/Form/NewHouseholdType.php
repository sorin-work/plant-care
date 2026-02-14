<?php

namespace App\Form;

use App\Entity\Household;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Same fields as the settings form, used when starting another household.
 *
 * @extends AbstractType<Household>
 */
final class NewHouseholdType extends AbstractType
{
    public function getParent(): string
    {
        return HouseholdSettingsType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Household::class]);
    }
}
