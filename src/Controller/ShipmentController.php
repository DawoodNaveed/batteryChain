<?php

namespace App\Controller;

use App\Entity\Battery;
use App\Entity\Manufacturer;
use App\Entity\Shipment;
use App\Entity\User;
use App\Enum\RoleEnum;
use App\Form\BulkDeliveryFormType;
use App\Form\ShipmentFormType;
use App\Helper\CustomHelper;
use App\Service\BatteryService;
use App\Service\ManufacturerService;
use App\Service\TransactionLogService;
use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ShipmentController
 * @package App\Controller
 * @property Security security
 * @property EntityManagerInterface entityManager
 * @property ManufacturerService manufacturerService
 * @property BatteryService batteryService
 * @property TranslatorInterface translator
 * @property TransactionLogService transactionLogService
 */
class ShipmentController extends CRUDController
{
    /**
     * RecyclerController constructor.
     * @param Security $security
     * @param EntityManagerInterface $entityManager
     * @param ManufacturerService $manufacturerService
     * @param BatteryService $batteryService
     * @param TranslatorInterface $translator
     * @param TransactionLogService $transactionLogService
     */
    public function __construct(
        Security $security,
        EntityManagerInterface $entityManager,
        ManufacturerService $manufacturerService,
        BatteryService $batteryService,
        TranslatorInterface $translator,
        TransactionLogService $transactionLogService
    ) {
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->manufacturerService = $manufacturerService;
        $this->batteryService = $batteryService;
        $this->translator = $translator;
        $this->transactionLogService = $transactionLogService;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function shipmentAction(Request $request): Response
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $manufacturer = null;

        if (!in_array(RoleEnum::ROLE_SUPER_ADMIN, $user->getRoles(), true) &&
            !in_array(RoleEnum::ROLE_ADMIN, $user->getRoles(), true) &&
            in_array(RoleEnum::ROLE_MANUFACTURER, $user->getRoles(), true) ) {
            $manufacturer = $user->getManufacturer();
        }

        $form = $this->createForm(ShipmentFormType::class);
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
                return new RedirectResponse($this->admin->generateUrl('shipment'));
            }

            /** @var Battery|null $battery */
            $battery = $this->batteryService->fetchBatteryBySerialNumber(
                $serialNumber,
                $manufacturer,
                $user->getManufacturer() ? false : true
            );

            if (empty($battery) || $battery->getStatus() === CustomHelper::BATTERY_STATUS_PRE_REGISTERED) {
                $this->addFlash('sonata_flash_error', 'Battery may not exist or registered!');
                return new RedirectResponse($this->admin->generateUrl('shipment'));
            }

            if (CustomHelper::BATTERY_STATUSES[$battery->getStatus()] >=
                CustomHelper::BATTERY_STATUSES[CustomHelper::BATTERY_STATUS_DELIVERED] ||
                ($this->transactionLogService->isExist($battery, CustomHelper::BATTERY_STATUS_DELIVERED))
            ) {
                $this->addFlash('sonata_flash_error', 'Battery is already delivered!');

                return new RedirectResponse($this->admin->generateUrl('list'));
            }

            /** If Admin / Super Admin - we will use battery's manufacturer's User */
            if (in_array(RoleEnum::ROLE_SUPER_ADMIN, $user->getRoles(), true) ||
                in_array(RoleEnum::ROLE_ADMIN, $user->getRoles(), true)) {
                $user = $battery->getManufacturer()->getUser();
            }

            $transactionLog = $this->transactionLogService->createTransactionLog($battery, CustomHelper::BATTERY_STATUS_DELIVERED);
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
            $shipment->setTransactionLog($transactionLog);
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

    /**
     * @param Request $request
     * @return Response
     */
    public function bulkDeliveryAction(Request $request): Response
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $manufacturers = null;

        // In-Case of Admin / Super Admin
        if (in_array(RoleEnum::ROLE_SUPER_ADMIN, $this->getUser()->getRoles(), true) ||
            in_array(RoleEnum::ROLE_ADMIN, $this->getUser()->getRoles(), true)) {
            $manufacturers = $this->manufacturerService->getManufactures($user);
        }

        $form = $this->createForm(BulkDeliveryFormType::class, null, [
            'manufacturer' => $manufacturers
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('cancel')->isClicked()) {
                return new RedirectResponse($this->generateUrl('sonata_admin_dashboard'));
            }

            $formData = $form->getData();
            /** @var Manufacturer $manufacturer */
            $manufacturer = $formData['manufacturer'] ?? null;
            $file = $request->files->all();

            if (!empty($file) && isset($file['bulk_delivery_form']['csv'])) {
                /** @var UploadedFile $file */
                $file = $file['bulk_delivery_form']['csv'];
                $validCsv = $this->batteryService->isValidCsv($file);
                if ($validCsv['error'] == 0) {
                    if (!empty($manufacturer)) {
                        $user = $manufacturer->getUser();
                    }

                    $addDelivery = $this->batteryService->extractCsvAndAddDeliveries($file, $user);

                    if (!empty($addDelivery) && !empty($addDelivery['error'])) {
                        foreach ($addDelivery['error'] as $error) {
                            $this->addFlash('warning', $this->translator->trans($error['message']));
                        }
                    }
                } else {
                    $this->addFlash('error', $this->translator->trans($validCsv['message']));
                }

                return new RedirectResponse($this->admin->generateUrl('list'));
            }
        }

        return $this->render(
            'bulk_deliveries.html.twig',
            array(
                'form' => $form->createView(),
            )
        );
    }
}