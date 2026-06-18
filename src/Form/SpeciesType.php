<?php

namespace App\Form;

use App\Entity\Household;
use App\Entity\Species;
use App\Enum\LightLevel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Species>
 */
final class SpeciesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('commonName', TextType::class, ['label' => 'Name', 'empty_data' => ''])
            ->add('scientificName', TextType::class, ['label' => 'Scientific name', 'empty_data' => '', 'help' => 'Or any name that tells it apart, e.g. "Begonia (grandma\'s)".'])
            ->add('waterIntervalDays', IntegerType::class, ['label' => 'Water every (days)', 'empty_data' => '7'])
            ->add('fertilizeIntervalDays', IntegerType::class, ['label' => 'Fertilize every (days)', 'required' => false, 'help' => 'Leave empty if it should not be fertilized.'])
            ->add('repotIntervalMonths', IntegerType::class, ['label' => 'Repot every (months)', 'empty_data' => '18'])
            ->add('light', EnumType::class, [
                'class' => LightLevel::class,
                'choice_label' => fn (LightLevel $l): string => $l->label(),
            ])
            ->add('toxicToPets', CheckboxType::class, ['label' => 'Toxic to cats and dogs', 'required' => false])
            ->add('careNotes', TextareaType::class, ['label' => 'Care notes', 'required' => false, 'attr' => ['rows' => 3]]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Species::class,
            'household' => null,
            'empty_data' => fn (\Symfony\Component\Form\FormInterface $form) => (new Species('', '', 7, LightLevel::Medium))
                ->setHousehold($form->getConfig()->getOption('household')),
        ]);
        $resolver->setAllowedTypes('household', ['null', Household::class]);
    }
}
