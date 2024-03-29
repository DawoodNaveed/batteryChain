<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class GenerateLabelFormType
 * @package App\Form
 */
class GenerateLabelFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options); // TODO: Change the autogenerated stub

        $builder
            ->add('battery', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Insert Serial Number'
                ],
                'required' => true,
           ])->add(
               'manufacturer',
            ChoiceType::class,
               [
                   'required' => $options['is_admin'],
                   'label' => 'Manufacturer',
                   'choices' => $options['manufacturer']
                ]
            )->add(
            'generate',
            SubmitType::class,
            ['label' => 'Generate Label', 'attr' => ['class' => 'btn btn-green', 'style'=> 'margin-top:10px;']]
        );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'manufacturer' => null,
            'is_admin' => false,
        ]);
    }
}