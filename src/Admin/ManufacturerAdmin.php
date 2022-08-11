<?php

namespace App\Admin;

use App\Entity\Manufacturer;
use App\Enum\RoleEnum;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\AdminType;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Vich\UploaderBundle\Form\Type\VichFileType;

/**
 * Class ManufacturerAdmin
 * @package App\Admin
 * @property $passwordEncoder
 */
class ManufacturerAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        parent::configureFormFields($form); // TODO: Change the autogenerated stub
        /** @var Manufacturer $manufacturer */
        $manufacturer = $this->getSubject();
        $form
            ->add('logoFile', VichFileType::class, array(
                'required'      => false,
                'allow_delete'  => false,
                'download_link' => false,
                'label' => false,
                'attr' => [
                    'style' => 'margin-top:10px'
                ]
            ))
            ->add('name', TextType::class);

        if ($manufacturer->getId() === null) {
            $form
                ->add('user', AdminType::class);
        } else {
            $form
                ->add('contact', TextType::class, [
                    'label' => 'Phone Number'
                ])
                ->add('address', TextType::class)
                ->add('postalCode', TextType::class, [
                    'label' => 'Post Code'
                ])
                ->add('city', TextType::class)
                ->add('country', TextType::class);
        }
    }

    /**
     * @param ListMapper $list
     */
    protected function configureListFields(ListMapper $list): void
    {
        parent::configureListFields($list); // TODO: Change the autogenerated
        $list
            ->addIdentifier('name')
            ->addIdentifier('address')
            ->addIdentifier('postalCode', TextType::class, [
                'label' => 'Post Code'
            ])
            ->addIdentifier('city')
            ->addIdentifier('country');
    }

    /**
     * @param DatagridMapper $filter
     */
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        parent::configureDatagridFilters($filter); // TODO: Change the autogenerated stub
        $filter->add('name');
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        parent::configureShowFields($show); // TODO: Change the autogenerated stub
        $show
            ->add('logo', null,  [
                'template' => 'manufacturer/show_logo_field.html.twig',
            ])
            ->add('name')
            ->add('user.email', EmailType::class, [
                'label' => 'Email'
            ])
            ->add('contact', TextType::class, [
                'label' => 'Phone Number'
            ])
            ->add('address')
            ->add('postalCode', TextType::class, [
                'label' => 'Post Code'
            ])
            ->add('city')
            ->add('country')
        ;
    }

    /**
     * @param $passwordEncoder
     */
    public function setPasswordEncoder($passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @param object $object
     */
    protected function prePersist(object $object): void
    {
        parent::prePersist($object); // TODO: Change the autogenerated stub

        $object->setUpdated(new \DateTime('now'));

        if ($object->getCreated() === null) {
            $object->setCreated(new \DateTime('now'));
        }

        $plainPassword = $object->getUser()->getPassword();
        $encoded = $this->passwordEncoder->hashPassword($object->getUser(), $plainPassword);

        $object->getUser()->setPassword($encoded);
        $object->getUser()->setRoles([RoleEnum::ROLE_MANUFACTURER]);
    }

    /**
     * @param RouteCollectionInterface $collection
     */
    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('create');
        $collection->remove('delete');
    }
}