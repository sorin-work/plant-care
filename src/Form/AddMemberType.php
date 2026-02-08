<?php

namespace App\Form;

use App\Enum\HouseholdRole;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<array{email: string, role: HouseholdRole}>
 */
final class AddMemberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email of their account',
                'empty_data' => '',
                'constraints' => [new NotBlank(), new Email()],
            ])
            ->add('role', EnumType::class, [
                'class' => HouseholdRole::class,
                'choice_label' => fn (HouseholdRole $r): string => $r->label(),
                'data' => HouseholdRole::Editor,
            ]);
    }
}
