<?php

namespace App\Form;

use App\Entity\Household;
use App\Entity\Plant;
use App\Entity\Room;
use App\Entity\Species;
use App\Enum\LightLevel;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Plant>
 */
final class PlantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Household $household */
        $household = $options['household'];

        $builder
            ->add('name', TextType::class, [
                'empty_data' => '',
                'attr' => ['placeholder' => 'e.g. Monty the monstera'],
            ])
            ->add('species', EntityType::class, [
                'class' => Species::class,
                'required' => false,
                'placeholder' => 'Not sure / other',
                'choice_label' => fn (Species $s): string => (string) $s,
                'query_builder' => fn (EntityRepository $r): QueryBuilder => $r->createQueryBuilder('s')->orderBy('s.commonName', 'ASC'),
            ])
            ->add('room', EntityType::class, [
                'class' => Room::class,
                'required' => false,
                'placeholder' => 'No room',
                'choice_label' => 'name',
                'query_builder' => fn (EntityRepository $r): QueryBuilder => $r->createQueryBuilder('r')
                    ->where('r.household = :household')
                    ->setParameter('household', $household)
                    ->orderBy('r.name', 'ASC'),
            ])
            ->add('light', EnumType::class, [
                'class' => LightLevel::class,
                'label' => 'Light where it stands',
                'choice_label' => fn (LightLevel $l): string => $l->label(),
            ])
            ->add('acquiredOn', DateType::class, [
                'label' => 'Since',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('notes', TextareaType::class, [
                'required' => false,
                'attr' => ['rows' => 4],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Plant::class]);
        $resolver->setRequired('household');
        $resolver->setAllowedTypes('household', Household::class);
    }
}
