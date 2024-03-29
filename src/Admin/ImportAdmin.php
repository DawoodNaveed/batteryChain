<?php

namespace App\Admin;

use App\Enum\RoleEnum;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Class ImportAdmin
 * @package App\Admin
 * @property $tokenStorage
 */
class ImportAdmin extends AbstractAdmin
{
    /**
     * @param $tokenStorage
     */
    public function setTokenStorage($tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    protected function configureListFields(ListMapper $list): void
    {
        parent::configureListFields($list); // TODO: Change the autogenerated stub
        $list
            ->addIdentifier('csv')
            ->addIdentifier('manufacturer.name', TextType::class, [
                'label' => 'Manufacturer'
            ])
            ->addIdentifier('status', TextType::class, [
                'template' => 'import/list__status_field.html.twig'
            ])
            ->addIdentifier(ListMapper::NAME_ACTIONS, null, [
                'header_style' => 'width: 30%;',
                'row_align' => 'center',
                'actions' => [
                    'details' => [
                        'template' => 'import/list__action_detail.html.twig',
                    ]
                ]
            ]);
    }

    /**
     * @param DatagridMapper $filter
     */
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        parent::configureDatagridFilters($filter); // TODO: Change the autogenerated stub
        $filter->add('manufacturer.name');
    }

    /**
     * @param RouteCollectionInterface $collection
     */
    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->add('details');
        $collection->remove('create');
        $collection->remove('delete');
        $collection->remove('show');
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
            $query->andWhere(
                $query->expr()->eq(current($query->getRootAliases()) . '.manufacturer', $user->getManufacturer()->getId())
            );
        }

        return $query;
    }

    /**
     * @param array $sortValues
     */
    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        $sortValues[DatagridInterface::SORT_BY] = 'id';
    }
}