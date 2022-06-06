<?php

namespace App\Admin;

use App\Entity\Manufacturer;
use App\Enum\RoleEnum;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Class BatteryReturnAdmin
 * @package App\Admin
 * @property $tokenStorage
 */
class BatteryReturnAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        parent::configureFormFields($form); // TODO: Change the autogenerated stub

        $user = $this->tokenStorage->getToken()->getUser();

        if (!in_array(RoleEnum::ROLE_SUPER_ADMIN, $user->getRoles(), true)
            && in_array(RoleEnum::ROLE_RECYCLER, $user->getRoles(), true)) {
            $manufacturers = $user->getRecycler()->getManufacturers();
            $batteries = [];

            /** @var Manufacturer $manufacturer */
            foreach ($manufacturers as $manufacturer) {
                $batteries = array_merge($batteries, $manufacturer->getBatteries()->toArray());
            }

            $form
                ->add('battery', ModelType::class, [
                    'property' => 'serialNumber',
                    'btn_add' => false,
                    'choices' => $batteries,
                ]);
        } else {
            $form
                ->add('battery', ModelType::class, [
                    'property' => 'serialNumber',
                    'btn_add' => false
                ]);
        }

        $form
            ->add('address', TextType::class)
            ->add('city', TextType::class)
            ->add('country', TextType::class);
    }

    /**
     * @param ListMapper $list
     */
    protected function configureListFields(ListMapper $list): void
    {
        parent::configureListFields($list); // TODO: Change the autogenerated
        $list
            ->addIdentifier('battery.serialNumber')
            ->addIdentifier('address')
            ->addIdentifier('city')
            ->addIdentifier('country');
    }

    /**
     * @param DatagridMapper $filter
     */
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        parent::configureDatagridFilters($filter); // TODO: Change the autogenerated stub
        $filter->add('battery.serialNumber');
    }

    /**
     * @param ShowMapper $show
     */
    protected function configureShowFields(ShowMapper $show): void
    {
        parent::configureShowFields($show); // TODO: Change the autogenerated stub
        $show
            ->add('battery.serialNumber')
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

        if ($object->getReturnDate() === null) {
            $object->setReturnDate(new \DateTime('now'));
        }

        if (!in_array(RoleEnum::ROLE_SUPER_ADMIN, $user->getRoles(), true)
            && in_array(RoleEnum::ROLE_RECYCLER, $user->getRoles(), true)) {
            $object->setReturnTo($user);
        }

        $object->setReturnFrom($object->getBattery()->getCurrentPossessor());
    }

    protected function preValidate(object $object): void
    {
        parent::preValidate($object); // TODO: Change the autogenerated stub
    }

}