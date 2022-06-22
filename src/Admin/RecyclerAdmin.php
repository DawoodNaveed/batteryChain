<?php

namespace App\Admin;

use App\Entity\Recycler;
use App\Enum\RoleEnum;
use Doctrine\ORM\Query\Expr\Join;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Class RecyclerAdmin
 * @package App\Admin
 * @property $passwordEncoder
 * @property $tokenStorage
 */
class RecyclerAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        parent::configureFormFields($form); // TODO: Change the autogenerated stub
        /** @var Recycler $recycler */
        $recycler = $this->getSubject();
        $form->add('name', TextType::class);

        $form->add('email', EmailType::class)
            ->add('contact', TextType::class)
            ->add('address', TextType::class)
            ->add('city', TextType::class)
            ->add('country', ModelType::class, [
                'property' => 'name',
                'btn_add' => false
            ]);
    }

    /**
     * @param ListMapper $list
     */
    protected function configureListFields(ListMapper $list): void
    {
        parent::configureListFields($list); // TODO: Change the autogenerated
        $list
            ->addIdentifier('name')
            ->addIdentifier('email')
            ->addIdentifier('address')
            ->addIdentifier('city')
            ->addIdentifier('country.name', TextType::class, ['label' => 'Country']);
    }

    /**
     * @param DatagridMapper $filter
     */
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        parent::configureDatagridFilters($filter); // TODO: Change the autogenerated stub
        $filter->add('name');
        $filter->add('email');
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        parent::configureShowFields($show); // TODO: Change the autogenerated stub
        $show
            ->add('name')
            ->add('email', EmailType::class, [
                'label' => 'Email'
            ])
            ->add('address')
            ->add('city')
            ->add('contact');
    }

    /**
     * @param $passwordEncoder
     */
    public function setPasswordEncoder($passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
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

        if (!in_array(RoleEnum::ROLE_SUPER_ADMIN, $user->getRoles(), true)) {
            $object->addManufacturer($user->getManufacturer());
        }
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->add('attach');
        $collection->add('getRecycler');
        $collection->add('bulkUpdate');
    }

    protected function configureBatchActions(array $actions): array
    {
        unset($actions['delete']);
        return parent::configureBatchActions($actions); // TODO: Change the autogenerated stub
    }

    /**
     * @param ProxyQueryInterface $query
     * @return ProxyQueryInterface
     */
    protected function configureQuery(ProxyQueryInterface $query): ProxyQueryInterface
    {
        $query = parent::configureQuery($query);
        $user = $this->tokenStorage->getToken()->getUser();

        if (!in_array(RoleEnum::ROLE_SUPER_ADMIN, $user->getRoles(), true)) {
            $manufacturer = $user->getManufacturer();
            $rootAlias = current($query->getRootAliases());
            $query->join($rootAlias . '.manufacturers', 'm', Join::WITH,
                $query->expr()->eq('m.id', $manufacturer->getId()));
        }

        return $query;
    }

    /**
     * @param array $actions
     * @return array
     */
    protected function configureDashboardActions(array $actions): array
    {
        $actions['bulkUpdate'] = [
            'label' => 'Bulk Insert/Update',
            'translation_domain' => 'SonataAdminBundle',
            'url' => $this->generateUrl('bulkUpdate'),
            'icon' => 'fa fa-plus',
        ];

        return $actions;
    }
}