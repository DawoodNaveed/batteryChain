<?php

namespace App\Admin;

use App\Enum\RoleEnum;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Class TransactionAdmin
 * @package App\Admin
 * @property $tokenStorage
 */
class TransactionAdmin extends AbstractAdmin
{
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
                $query->expr()->eq($rootAlias . '.fromUser', $user->getId())
            );
        }

        $query->andWhere(
            $query->expr()->in($rootAlias . '.transactionType', ['returned', 'recycled'])
        );

        return $query;
    }


    /**
     * @param ListMapper $list
     */
    protected function configureListFields(ListMapper $list): void
    {
        parent::configureListFields($list); // TODO: Change the autogenerated
        $list
            ->addIdentifier('battery.serialNumber')
            ->addIdentifier('returnTo.name', TextType::class, [
                'label' => 'Recycler Name',
                'template' => 'return/list_recycler_name_field.html.twig'
            ])
            ->addIdentifier('transactionType', TextType::class, [
                'template' => 'return/list_transaction_type_field.html.twig'
            ])
            ->addIdentifier('created', null, [
                'label' => 'Return/Recycle Date',
                'format' => 'Y-m-d h:s:i'
            ]);
    }

    /**
     * @param DatagridMapper $filter
     */
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        parent::configureDatagridFilters($filter); // TODO: Change the autogenerated stub
        $filter->add('battery.serialNumber');
        $filter->add('transactionType');
    }

    /**
     * @param ShowMapper $show
     */
    protected function configureShowFields(ShowMapper $show): void
    {
        parent::configureShowFields($show); // TODO: Change the autogenerated stub
        $show
            ->add('battery.serialNumber')
            ->add('transactionType')
            ->add('returnTo', null, [
                'label' => 'Recycler'
            ])
            ->add('address')
            ->add('city')
            ->add('country');
    }

    /**
     * @param $tokenStorage
     */
    public function setTokenStorage($tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param array $actions
     * @return array
     */
    protected function configureBatchActions(array $actions): array
    {
        unset($actions['delete']);
        return parent::configureBatchActions($actions); // TODO: Change the autogenerated stub
    }

    /**
     * @param RouteCollectionInterface $collection
     */
    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection
            ->remove('create')
            ->remove('delete')
            ->remove('edit')
            ->add('return')
            ->add('getRecyclerByManufacturer')
            ->add('bulkReturn')
            ->add('recycle')
            ->add('bulkRecycle');
    }

    /**
     * @param array $actions
     * @return array
     */
    protected function configureDashboardActions(array $actions): array
    {
        $actions['return'] = [
            'label' => 'Add Return',
            'translation_domain' => 'SonataAdminBundle',
            'url' => $this->generateUrl('return'),
            'icon' => 'fa fa-plus',
        ];
        $actions['bulkReturn'] = [
            'label' => 'Add Bulk Return',
            'translation_domain' => 'SonataAdminBundle',
            'url' => $this->generateUrl('bulkReturn'),
            'icon' => 'fa fa-file',
        ];
        $actions['bulkRecycle'] = [
            'label' => 'Add Bulk Recycle',
            'translation_domain' => 'SonataAdminBundle',
            'url' => $this->generateUrl('bulkRecycle'),
            'icon' => 'fa fa-recycle',
        ];
        $actions['recycle'] = [
            'label' => 'Add Recycle',
            'translation_domain' => 'SonataAdminBundle',
            'url' => $this->generateUrl('recycle'),
            'icon' => 'fa fa-recycle',
        ];

        return $actions;
    }
}