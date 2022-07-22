<?php

namespace App\Admin;

use App\Entity\Battery;
use App\Enum\RoleEnum;
use App\Helper\CustomHelper;
use App\Service\TransactionLogService;
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
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Class BatteryFilteredAdmin
 * @package App\Admin
 * @property $tokenStorage
 * @property TransactionLogService transactionLogService
 */
class BatteryFilteredAdmin extends AbstractAdmin
{
    protected $baseRouteName = 'battery-intermediate_battery';

    protected $baseRoutePattern = 'battery-intermediate';
    
    protected function configureFormFields(FormMapper $form): void
    {
        parent::configureFormFields($form); // TODO: Change the autogenerated stub
        /** @var Battery $battery */
        $battery = $this->getSubject();
        $disabled = false;

        if ($battery->getId() !== null && $battery->getStatus() !== CustomHelper::BATTERY_STATUS_PRE_REGISTERED) {
            $disabled = true;
        }

        $user = $this->tokenStorage->getToken()->getUser();

        if (in_array(RoleEnum::ROLE_SUPER_ADMIN, $user->getRoles(), true) ||
            in_array(RoleEnum::ROLE_ADMIN, $user->getRoles(), true)) {
            $form
                ->add('manufacturer', ModelType::class, [
                    'property' => 'name',
                    'btn_add' => false,
                    'disabled' => $disabled
            ]);
        }

        $form
            ->add('serialNumber', TextType::class, [
                'disabled' => $disabled
            ])
            ->add('batteryType', ModelType::class, [
                'property' => 'type',
                'btn_add' => false,
                'disabled' => $disabled
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
                'required' => false
            ])
            ->add('nominalVoltage', NumberType::class, [
                'disabled' => $disabled
            ])
            ->add('nominalCapacity', NumberType::class, [
                'disabled' => $disabled
            ])
            ->add('nominalEnergy', NumberType::class, [
                'disabled' => $disabled
            ])
            ->add('acidVolume', TextType::class, [
                'required' => true
            ])
            ->add('co2', TextType::class, [
                'label' => 'CO2',
                'required' => true
            ])
            ->add('height', NumberType::class, [
                'required' => true,
                'disabled' => $disabled
            ])
            ->add('width', NumberType::class, [
                'required' => true,
                'disabled' => $disabled
            ])
            ->add('length', NumberType::class, [
                'required' => true,
                'disabled' => $disabled
            ])
            ->add('mass', NumberType::class, [
                'label' => 'Weight',
                'disabled' => $disabled
            ]);

        if ($battery->getId() === null) {
            $form
                ->add('register', CheckboxType::class, [
                    'mapped' => false,
                    'required' => false,
                    'label' => 'Is fully registered?',
                    'disabled' => $disabled
            ]);
        }

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
            ->addIdentifier('status')
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
            ->addIdentifier('mass', TextType::class, [
                'label' => 'Weight'
            ]);
    }

    /**
     * @param DatagridMapper $filter
     */
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        parent::configureDatagridFilters($filter); // TODO: Change the autogenerated stub
        $filter->add('serialNumber')
            ->add('status');
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
            ->add('status', TextType::class, [
                'template' => 'battery/show_status_field.html.twig'
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
            ->add('length')
            ->add('mass', TextType::class, ['label' => 'Weight']);
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
            && !in_array(RoleEnum::ROLE_ADMIN, $user->getRoles(), true)
            && in_array(RoleEnum::ROLE_MANUFACTURER, $user->getRoles(), true)) {
            $manufacturerId = $user->getManufacturer()->getId();
        } else {
            $manufacturerId = $this->getRequest()->get('manufacturer');
        }

        $rootAlias = current($query->getRootAliases());
        $query
            ->andWhere(
                $query->expr()->eq($rootAlias . '.manufacturer', $manufacturerId));
        $query->andWhere(
            $query->expr()->eq($rootAlias . '.isBulkImport', 1));

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
     * @param TransactionLogService $transactionLogService
     */
    public function setTransactionLogService(TransactionLogService $transactionLogService)
    {
        $this->transactionLogService = $transactionLogService;
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

        if ((!in_array(RoleEnum::ROLE_SUPER_ADMIN, $user->getRoles(), true)
            && !in_array(RoleEnum::ROLE_ADMIN, $user->getRoles(), true)
            && in_array(RoleEnum::ROLE_MANUFACTURER, $user->getRoles(), true))) {
            $object->setManufacturer($user->getManufacturer());
        }

        $object->setStatus(CustomHelper::BATTERY_STATUS_PRE_REGISTERED);
        $object->setBlockchainSecured(false);
        $object->setCurrentPossessor($user);
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('create');
        $collection->add('import');
        $collection->add('detail');
        $collection->add('download');
        $collection->add('register');
    }

    /**
     * @param array $actions
     * @return array
     */
    protected function configureBatchActions(array $actions): array
    {
        if (
            $this->hasRoute('edit') && $this->hasAccess('edit') &&
            $this->hasRoute('delete') && $this->hasAccess('delete')
        ) {
            $actions['register'] = [
                'ask_confirmation' => true,
                'controller' => 'app.controller.battery::batchRegisterAction',
                'template' => 'battery/ask_confirmation.html.twig'
            ];
        }

        return $actions;
    }

    protected function postPersist(object $object): void
    {
        try {
            parent::postPersist($object); // TODO: Change the autogenerated stub

            if (($this->getForm()->get('register')->getData())) {
                $object->setStatus(CustomHelper::BATTERY_STATUS_REGISTERED);
                /** @var Battery $object */
                $this->transactionLogService->createTransactionLog(
                    $object,
                    CustomHelper::BATTERY_STATUS_REGISTERED
                );
            }
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }
    }
}