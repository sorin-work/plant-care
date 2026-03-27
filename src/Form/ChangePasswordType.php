<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<array{current: string, password: string}>
 */
final class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('current', PasswordType::class, [
                'label' => 'Current password',
                'constraints' => [new UserPassword(message: 'This is not your current password.')],
                'attr' => ['autocomplete' => 'current-password'],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => ['label' => 'New password', 'attr' => ['autocomplete' => 'new-password']],
                'second_options' => ['label' => 'Repeat new password', 'attr' => ['autocomplete' => 'new-password']],
                'invalid_message' => 'The new passwords do not match.',
                'constraints' => [new NotBlank(), new Length(min: 8, max: 4096)],
            ]);
    }
}
