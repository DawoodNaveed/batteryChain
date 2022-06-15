<?php


namespace App\Controller;

use App\Entity\Battery;
use App\Entity\Distributor;
use App\Entity\Recycler;
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
        $manufacturer = $user->getManufacturer();
        $batteries = $this->batteryService->getCurrentPossessedBatteries($user);
        $form = $this->createForm(ShipmentFormType::class, null, [
            'recyclers' => $manufacturer->getRecyclers()->toArray(),
            'distributors' => $manufacturer->getDistributors()->toArray(),
            'batteries' => $batteries,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted())
        {
            $formData = $form->getData();
            $shipToType = $formData['linkType'];
            $shipTo = null;
            $batteries = $formData['battery'];

            if ($shipToType == 'distributors') {
                /** @var Distributor $shipTo */
                $shipTo = $formData['distributors'];
                if (empty($shipTo)) {
                    $this->addFlash('sonata_flash_error', 'Kindly Select Distributor!');
                    return new RedirectResponse($this->generateUrl('sonata_admin_dashboard'));
                }
            }

            if ($shipToType == 'recyclers') {
                /** @var Recycler $shipTo */
                $shipTo = $formData['recyclers'];

                if (empty($shipTo)) {
                    $this->addFlash('sonata_flash_error', 'Kindly Select Recycler!');
                    return new RedirectResponse($this->generateUrl('sonata_admin_dashboard'));
                }
            }

            $totalBatteries = count($batteries);
            $shippedBatteriesCount = 0;
            /**
             * @var int $key
             * @var Battery $battery
             */
            foreach ($batteries as $key => $battery) {
                if (CustomHelper::BATTERY_STATUSES[$battery->getStatus()] >=
                    CustomHelper::BATTERY_STATUSES[$formData['status']]) {
                    continue;
                }

                $shipment = new Shipment();
                $shipment->setUpdated(new \DateTime('now'));
                $shipment->setCreated(new \DateTime('now'));
                $shipment->setAddress($formData['address']);
                $shipment->setCity($formData['city']);
                $shipment->setCountry($formData['country']);
                $shipment->setShipmentDate(new \DateTime('now'));
                $shipment->setShipmentFrom($user);
                $shipment->setShipmentTo($shipTo->getUser());
                $shipment->setBattery($battery);
                $battery->setStatus($formData['status']);
                $battery->setCurrentPossessor($shipTo->getUser());

                $this->entityManager->persist($shipment);
                $shippedBatteriesCount++;
            }

            $this->entityManager->flush();

            if ($totalBatteries === $shippedBatteriesCount) {
                $this->addFlash('sonata_flash_success', 'Successfully Added All Shipments!');
            }

            $this->addFlash('sonata_flash_info', 'Only ' . $shippedBatteriesCount . ' Batteries are updated, out of Total: ' . $totalBatteries . ' Batteries');

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