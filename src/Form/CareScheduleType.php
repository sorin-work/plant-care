<?php

namespace App\Form;

use App\Entity\CareSchedule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<CareSchedule>
 */
final class CareScheduleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('intervalDays', IntegerType::class, [
                'label' => 'Every (days)',
                'empty_data' => '0',
                'attr' => ['min' => 1, 'max' => 1825],
            ])
            ->add('seasonal', CheckboxType::class, [
                'label' => 'Adjust for the season (less in winter, more in summer)',
                'required' => false,
            ])
            ->add('nextDueOn', DateType::class, [
                'label' => 'Next due',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('notes', TextType::class, [
                'label' => 'How',
                'required' => false,
                'attr' => ['placeholder' => 'e.g. bottom water, about 300 ml'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => CareSchedule::class]);
    }
}
