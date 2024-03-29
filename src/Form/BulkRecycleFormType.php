<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class BulkRecycleFormType
 * @package App\Form
 */
class BulkRecycleFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options); // TODO: Change the autogenerated stub

        if (!empty($options['manufacturer'])) {
            $builder->add(
                'manufacturer',
                ChoiceType::class,
                [
                    'required' => true,
                    'label' => 'Manufacturer',
                    'choices' => array_merge(['Kindly Select Manufacturer' => ''], $options['manufacturer'])
                ]
            );
        }

        $builder
            ->add('recycler', ChoiceType::class, [
                'multiple' => false,
                'choices' => $options['recyclers']
            ])
            ->add('csv', FileType::class, ['required' => true, 'label' => 'CSV']);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'manufacturer' => null,
            'recyclers' => null
        ]);
    }
}