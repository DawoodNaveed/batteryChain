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
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Class BatteryAdmin
 * @package App\Admin
 * @property $tokenStorage
 */
class BatteryAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        parent::configureFormFields($form); // TODO: Change the autogenerated stub
        /** @var Battery $battery */
        $battery = $this->getSubject();

        $user = $this->tokenStorage->getToken()->getUser();

        if (in_array(RoleEnum::ROLE_SUPER_ADMIN, $user->getRoles(), true)) {
            $form
                ->add('manufacturer', ModelType::class, [
                    'property' => 'name',
                    'btn_add' => false,
            ]);
        }

        $form
            ->add('serialNumber', TextType::class)
            ->add('batteryType', ModelType::class, [
                'property' => 'type',
                'btn_add' => false
            ])
            ->add('cellType', TextType::class, [
                'required' => false
            ])
            ->add('moduleType', TextType::class, [
                'required' => false
            ])
            ->add('trayNumber', TextType::class)
            ->add('productionDate', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('nominalVoltage', NumberType::class)
            ->add('nominalCapacity', NumberType::class)
            ->add('nominalEnergy', NumberType::class)
            ->add('acidVolume', TextType::class, [
                'required' => false
            ])
            ->add('co2', TextType::class, [
                'label' => 'CO2',
                'required' => false
            ])
            ->add('cycleLife', NumberType::class, [
                'required' => false
            ])
            ->add('height', NumberType::class, [
                'required' => false
            ])
            ->add('width', NumberType::class, [
                'required' => false
            ])
            ->add('mass', NumberType::class);
    }

    /**
     * @param ListMapper $list
     */
    protected function configureListFields(ListMapper $list): void
    {
        parent::configureListFields($list); // TODO: Change the autogenerated
        $list
            ->addIdentifier('manufacturer.name')
            ->addIdentifier('serialNumber')
            ->addIdentifier('batteryType.type', TextType::class, [
                'label' => 'Battery Type'
            ])
            ->addIdentifier('cellType')
            ->addIdentifier('moduleType')
            ->addIdentifier('productionDate', null, [
                'format' => 'Y-m-d'
            ])
            ->addIdentifier('trayNumber')
            ->addIdentifier('co2', TextType::class, [
                'label' => 'CO2'
            ])
            ->addIdentifier('nominalVoltage')
            ->addIdentifier('nominalCapacity')
            ->addIdentifier('nominalEnergy')
            ->addIdentifier('mass');
    }

    /**
     * @param DatagridMapper $filter
     */
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        parent::configureDatagridFilters($filter); // TODO: Change the autogenerated stub
        $filter->add('serialNumber');
    }

    /**
     * @param ShowMapper $show
     */
    protected function configureShowFields(ShowMapper $show): void
    {
        parent::configureShowFields($show); // TODO: Change the autogenerated stub
        $show
            ->add('manufacturer.name')
            ->add('serialNumber')
            ->add('batteryType.type', TextType::class, [
                'label' => 'Battery Type'
            ])
            ->add('cellType')
            ->add('moduleType')
            ->add('productionDate', null, [
                'format' => 'Y-m-d'
            ])
            ->add('trayNumber')
            ->add('co2', TextType::class, [
                'label' => 'CO2'
            ])
            ->add('nominalVoltage')
            ->add('nominalCapacity')
            ->add('nominalEnergy')
            ->add('height')
            ->add('width')
            ->add('status');
    }

    /**
     * @param ProxyQueryInterface $query
     * @return ProxyQueryInterface
     */
    protected function configureQuery(ProxyQueryInterface $query): ProxyQueryInterface
    {
        $query = parent::configureQuery($query);
        $user = $this->tokenStorage->getToken()->getUser();

        if (!in_array(RoleEnum::ROLE_SUPER_ADMIN, $user->getRoles(), true)
            && in_array(RoleEnum::ROLE_MANUFACTURER, $user->getRoles(), true)) {
            $manufacturer = $user->getManufacturer();
            $rootAlias = current($query->getRootAliases());
            $query->andWhere(
                $query->expr()->eq($rootAlias . '.currentPossessor', $user->getId())
            );
        }

        return $query;
    }
    /**
     * @param $tokenStorage
     */
    public function setTokenStorage($tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param object $object
     */
    protected function prePersist(object $object): void
    {
        parent::prePersist($object); // TODO: Change the autogenerated stub

        $object->setUpdated(new \DateTime('now'));
        $user = $this->tokenStorage->getToken()->getUser();

        if ($object->getCreated() === null) {
            $object->setCreated(new \DateTime('now'));
        }

        if (!in_array(RoleEnum::ROLE_SUPER_ADMIN, $user->getRoles(), true)
        && in_array(RoleEnum::ROLE_MANUFACTURER, $user->getRoles(), true)) {
            $object->setManufacturer($user->getManufacturer());
        }

        $object->setStatus(CustomHelper::BATTERY_STATUS_REGISTERED);
        $object->setCurrentPossessor($user);
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->add('import');
        $collection->add('detail');
        $collection->add('download');
    }

    /**
     * @param array $actions
     * @return array
     */
    protected function configureDashboardActions(array $actions): array
    {
        $actions['import'] = [
            'label' => 'Bulk Import',
            'translation_domain' => 'SonataAdminBundle',
            'url' => $this->generateUrl('import'),
            'icon' => 'fa fa-plus',
        ];
        $actions['detail'] = [
            'label' => 'Detail View',
            'translation_domain' => 'SonataAdminBundle',
            'url' => $this->generateUrl('detail'),
            'icon' => 'fa fa-info-circle',
        ];

        return $actions;
    }
}