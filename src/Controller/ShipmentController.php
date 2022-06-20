<?php

namespace App\Controller;

use App\Entity\Battery;
use App\Entity\Shipment;
use App\Entity\User;
use App\Form\ShipmentFormType;
use App\Helper\CustomHelper;
use App\Service\BatteryService;
use App\Service\ManufacturerService;
use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;

/**
 * Class ShipmentController
 * @package App\Controller
 * @property Security security
 * @property EntityManagerInterface entityManager
 * @property ManufacturerService manufacturerService
 * @property BatteryService batteryService
 */
class ShipmentController extends CRUDController
{
    /**
     * RecyclerController constructor.
     * @param Security $security
     * @param EntityManagerInterface $entityManager
     * @param ManufacturerService $manufacturerService
     * @param BatteryService $batteryService
     */
    public function __construct(
        Security $security,
        EntityManagerInterface $entityManager,
        ManufacturerService $manufacturerService,
        BatteryService $batteryService
    ) {
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->manufacturerService = $manufacturerService;
        $this->batteryService = $batteryService;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function shipmentAction(Request $request): Response
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $batteries = $this->batteryService->getCurrentPossessedBatteries($user);
        $form = $this->createForm(ShipmentFormType::class, null, [
            'batteries' => $batteries,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted())
        {
            if ($form->get('cancel')->isClicked()) {
                return new RedirectResponse($this->generateUrl('sonata_admin_dashboard'));
            }

            $formData = $form->getData();
            $serialNumber = $formData['battery'] ?? null;

            if (empty($serialNumber)) {
                $this->addFlash('sonata_flash_error', 'Kindly Insert Valid Battery Serial Number!');
                return new RedirectResponse($this->admin->generateUrl('list'));
            }

            /** @var Battery|null $battery */
            $battery = $this->batteryService->fetchBatteryBySerialNumber($serialNumber);

            if (empty($battery)) {
                $this->addFlash('sonata_flash_error', 'Battery does not exist!');
                return new RedirectResponse($this->admin->generateUrl('list'));
            }

            if (CustomHelper::BATTERY_STATUSES[$battery->getStatus()] >=
                CustomHelper::BATTERY_STATUSES[CustomHelper::BATTERY_STATUS_DELIVERED]) {
                $this->addFlash('sonata_flash_error', 'Battery is already delivered!');

                return new RedirectResponse($this->admin->generateUrl('list'));
            }

            $shipment = new Shipment();
            $shipment->setUpdated(new \DateTime('now'));
            $shipment->setCreated(new \DateTime('now'));
            $shipment->setAddress($formData['information']['address']);
            $shipment->setCity($formData['information']['city']);
            $shipment->setCountry($formData['information']['country']);
            $shipment->setShipmentDate(new \DateTime('now'));
            $shipment->setShipmentFrom($user);
            $shipment->setShipmentTo($user);
            $shipment->setBattery($battery);
            $battery->setStatus(CustomHelper::BATTERY_STATUS_DELIVERED);
            $battery->setCurrentPossessor($user);

            $this->entityManager->persist($shipment);
            $this->entityManager->flush();

            $this->addFlash('sonata_flash_success', 'Successfully Added All Shipments!');

            return new RedirectResponse($this->admin->generateUrl('list'));
        }

        return $this->render(
            'shipment_batteries.html.twig',
            array(
                'form' => $form->createView(),
            )
        );
    }
}