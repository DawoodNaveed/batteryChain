<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Vich\UploaderBundle\Form\Type\VichFileType;

/**
 * Class UpdateProfileFormType
 * @package App\Form
 */
class UpdateProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('logoFile', VichFileType::class, array(
                'required'      => false,
                'allow_delete'  => false,
                'download_link' => false,
                'label' => 'Logo',
                'attr' => [
                    'style' => 'margin-top:10px'
                ]
            ))
            ->add('co2LogoFile', VichFileType::class, array(
                'required'      => false,
                'allow_delete'  => false,
                'download_link' => false,
                'label' => 'Climate Neutral Logo',
                'attr' => [
                    'style' => 'margin-top:10px'
                ]
            ))
            ->add('insuranceLogoFile', VichFileType::class, array(
                'required'      => false,
                'allow_delete'  => false,
                'download_link' => false,
                'label' => 'Insurance Logo',
                'attr' => [
                    'style' => 'margin-top:10px'
                ]
            ))
            ->add('firstname', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'value' => $options['firstname']
                ],
                'label_attr' => [
                    'style' => 'margin-top:15px'
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
                'label_attr' => [
                    'style' => 'margin-top:15px'
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
                'label_attr' => [
                    'style' => 'margin-top:15px'
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
                ],
                'label_attr' => [
                    'style' => 'margin-top:15px'
                ],
                'label' => 'Phone Number'
            ])
            ->add('postalCode', TextType::class, [
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'Post Code',
                'label_attr' => [
                    'style' => 'margin-top:15px'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a postal code',
                    ]),
                ]
            ])
            ->add('city', TextType::class, [
                'attr' => [
                    'class' => 'form-control'
                ],
                'label_attr' => [
                    'style' => 'margin-top:15px'
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
                'label_attr' => [
                    'style' => 'margin-top:15px'
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