<?php

namespace App\Admin;

use App\Entity\Battery;
use App\Enum\RoleEnum;
use App\Helper\CustomHelper;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Class ModifiedBatteryAdmin
 * @package App\Admin
 * @property $tokenStorage
 */
class ModifiedBatteryAdmin extends AbstractAdmin
{
    /**
     * @param FormMapper $form
     * @return void
     */
    protected function configureFormFields(FormMapper $form): void
    {
        parent::configureFormFields($form); // TODO: Change the autogenerated stub
        /** @var Battery $battery */
        $battery = $this->getSubject()->getBattery();
        $disabled = false;

        if (!empty($battery) && $battery->getId() !== null && $battery->getStatus() !== CustomHelper::BATTERY_STATUS_PRE_REGISTERED) {
            $disabled = true;
        }

        $form
            ->add('battery.serialNumber', TextType::class, [
                'disabled' => $disabled
            ])
            ->add('battery.batteryType', ModelType::class, [
                'property' => 'type',
                'btn_add' => false,
                'disabled' => $disabled
            ])
            ->add('battery.cellType', TextType::class, [
                'required' => false,
                'disabled' => $disabled
            ])
            ->add('battery.moduleType', TextType::class, [
                'required' => false,
                'disabled' => $disabled
            ])
            ->add('battery.trayNumber', TextType::class, [
                'disabled' => $disabled
            ])
            ->add('battery.productionDate', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false,
                'disabled' => $disabled
            ])
            ->add('battery.nominalVoltage', null, [
                'disabled' => $disabled,
                'invalid_message' => "This value '{{ value }}' should be of type number."
            ])
            ->add('battery.nominalCapacity', null, [
                'disabled' => $disabled,
                'invalid_message' => "This value '{{ value }}' should be of type number."
            ])
            ->add('battery.nominalEnergy', null, [
                'disabled' => $disabled,
                'invalid_message' => "This value '{{ value }}' should be of type number."
            ])
            ->add('battery.acidVolume', null, [
                'required' => true,
                'invalid_message' => "This value '{{ value }}' should be of type number.",
                'disabled' => $disabled
            ])
            ->add('battery.co2', null, [
                'label' => 'CO2',
                'required' => true,
                'invalid_message' => "This value '{{ value }}' should be of type number.",
                'disabled' => $disabled
            ])
            ->add('battery.height', null, [
                'required' => true,
                'disabled' => $disabled,
                'invalid_message' => "This value '{{ value }}' should be of type number."
            ])
            ->add('battery.width', null, [
                'required' => true,
                'disabled' => $disabled,
                'invalid_message' => "This value '{{ value }}' should be of type number."
            ])
            ->add('battery.length', null, [
                'required' => true,
                'disabled' => $disabled,
                'invalid_message' => "This value '{{ value }}' should be of type number."
            ])
            ->add('battery.mass', null, [
                'required' => true,
                'label' => 'Weight',
                'disabled' => $disabled,
                'invalid_message' => "This value '{{ value }}' should be of type number."
            ])
            ->add('battery.isInsured', CheckboxType::class, [
                'required' => false,
                'label' => 'Has Insurance?',
                'disabled' => $disabled,
            ])
            ->add('battery.isClimateNeutral', CheckboxType::class, [
                'required' => false,
                'label' => 'Is Climate Neutral?',
                'disabled' => $disabled,
            ]);
    }

    /**
     * @param ShowMapper $show
     */
    protected function configureShowFields(ShowMapper $show): void
    {
        parent::configureShowFields($show); // TODO: Change the autogenerated stub
        $show
            ->add('manufacturer.name')
            ->add('battery.serialNumber')
            ->add('battery.batteryType.type', TextType::class, [
                'label' => 'Battery Type'
            ])
            ->add('battery.status', TextType::class, [
                'template' => 'battery/show_status_field.html.twig'
            ])
            ->add('battery.cellType')
            ->add('battery.moduleType')
            ->add('battery.productionDate', null, [
                'format' => 'Y-m-d'
            ])
            ->add('battery.trayNumber')
            ->add('battery.co2', TextType::class, [
                'label' => 'CO2'
            ])
            ->add('battery.nominalVoltage')
            ->add('battery.nominalCapacity')
            ->add('battery.nominalEnergy')
            ->add('battery.height')
            ->add('battery.width')
            ->add('battery.length')
            ->add('battery.mass', TextType::class, ['label' => 'Weight'])
            ->add('battery.isInsured', null, ['label' => 'Has Insurance?'])
            ->add('battery.isClimateNeutral', null, ['label' => 'Is Climate Neutral?']);
    }

    /**
     * @param ListMapper $list
     */
    protected function configureListFields(ListMapper $list): void
    {
        parent::configureListFields($list); // TODO: Change the autogenerated
        $list
            ->addIdentifier('manufacturer.name')
            ->addIdentifier('battery.serialNumber')
            ->addIdentifier('battery.status')
            ->addIdentifier('battery.batteryType.type', TextType::class, [
                'label' => 'Battery Type'
            ])
            ->addIdentifier('battery.cellType')
            ->addIdentifier('battery.moduleType')
            ->addIdentifier('battery.productionDate', null, [
                'format' => 'Y-m-d'
            ])
            ->addIdentifier('battery.trayNumber')
            ->addIdentifier('battery.co2', TextType::class, [
                'label' => 'CO2'
            ])
            ->addIdentifier('battery.nominalVoltage')
            ->addIdentifier('battery.nominalCapacity')
            ->addIdentifier('battery.nominalEnergy')
            ->addIdentifier('battery.mass', TextType::class, [
                'label' => 'Weight'
            ])
            ->addIdentifier('action');
    }

    /**
     * @param DatagridMapper $filter
     */
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        parent::configureDatagridFilters($filter); // TODO: Change the autogenerated stub
        $filter->add('serialNumber')
            ->add('status')
            ->add('manufacturer.name');
    }

    /**
     * @param RouteCollectionInterface $collection
     * @return void
     */
    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('create');
        $collection->remove('delete');
    }
    
    /**
     * @param $tokenStorage
     */
    public function setTokenStorage($tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }
    
    /**
     * @param ProxyQueryInterface $query
     * @return ProxyQueryInterface
     */
    protected function configureQuery(ProxyQueryInterface $query): ProxyQueryInterface
    {
        $query = parent::configureQuery($query);
        $user = $this->tokenStorage->getToken()->getUser();
        $rootAlias = current($query->getRootAliases());
        
        if (!in_array(RoleEnum::ROLE_SUPER_ADMIN, $user->getRoles(), true)
            && !in_array(RoleEnum::ROLE_ADMIN, $user->getRoles(), true)) {
            $query->andWhere(
                $query->expr()->eq($rootAlias . '.modifiedBy', $user->getId())
            );
        }
        
        return $query;
    }
}