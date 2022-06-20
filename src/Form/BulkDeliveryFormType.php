<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class BulkImportBatteryFormType
 * @package App\Form
 */
class BulkDeliveryFormType extends AbstractType
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
                    'choices' => $options['manufacturer']
                ]
            );
        }

        $builder->add('csv', FileType::class, ['required' => true, 'label' => 'CSV']);
        $builder->add(
            'save',
            SubmitType::class,
            ['label' => 'Add Bulk Delivery', 'attr' => ['class' => 'btn btn-primary', 'style'=> 'margin-top:10px;']]
        );
        $builder->add(
            'cancel',
            SubmitType::class,
            [
                'label' => 'Cancel',
                'attr' => [
                    'class' => 'btn btn-default',
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
            'manufacturer' => null,
        ]);
    }
}