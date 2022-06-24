<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ReturnPublicFormType
 * @package App\Form
 */
class ReturnPublicFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options); // TODO: Change the autogenerated stub
        $builder
            ->add('country', ChoiceType::class, [
                'multiple' => false,
                'required' => true,
                'choices' => array_merge([
                    'Select Country' => ''
                ], $options['countries']),
                'attr' => [
                    'class' => 'form-select',
                    'size' => 1
                ],
                'label_attr' => [
                    'class' => 'fw-bold'
                ]
            ])
            ->add('recyclers', ChoiceType::class, [
                'multiple' => false,
                'required' => true,
                'choices' => array_merge([
                    'Select Recycler' => ""
                ], $options['recyclers']),
                'attr' => [
                    'class' => 'form-select'
                ],
                'label_attr' => [
                    'class' => 'fw-bold'
                ],
            ])
            ->add('recyclerId', HiddenType::class, [])
            ->add(
                $builder->create('information', FormType::class, [
                    'by_reference' => false,
                    'label_attr' => ['style' => 'font-size: medium', 'class' => 'fw-bold'],
                    'required' => true, 'label' => 'User Information',
                ])
                    ->add('name', TextType::class, [
                        'attr' => [
                            'class' => 'form-control'
                        ],
                        'required' => true,
                        'label_attr' => [
                            'class' => 'fw-bold'
                        ]
                    ])
                    ->add('email', TextType::class, [
                        'attr' => [
                            'class' => 'form-control'
                        ],
                        'required' => true,
                        'label_attr' => [
                            'class' => 'fw-bold'
                        ]
                    ])
            );

        $builder->add(
            'submit',
            SubmitType::class,
            ['label' => 'Add Return', 'attr' => ['class' => 'btn btn-primary mr-5', 'style'=> 'margin-top:10px;']]
        )
            ->add('cancel',
                SubmitType::class,
                [
                    'label' => 'Cancel',
                    'attr' => [
                        'class' => 'btn btn-light',
                        'style'=> 'margin-top:10px;',
                        'formnovalidate'=>'formnovalidate'
                    ]
                ]
            );
    }


    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'recyclers' => [],
            'countries' => [],
        ]);
    }
}