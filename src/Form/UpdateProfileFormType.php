<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class UpdateProfileFormType
 * @package App\Form
 */
class UpdateProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'value' => $options['firstname']
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a name',
                    ]),
                ],
                'mapped' => false
            ])
            ->add('lastname', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'value' => $options['lastname']
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a name',
                    ]),
                ],
                'mapped' => false
            ])
            ->add('address', TextType::class, [
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter an address',
                    ]),
                ]
            ])
            ->add('contact', TextType::class, [
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a valid number',
                    ]),
                ]
            ])
            ->add('city', TextType::class, [
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a city',
                    ]),
                ]
            ])
            ->add('country', TextType::class, [
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a country',
                    ]),
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'firstname' => null,
            'lastname' => null
        ]);
    }
}