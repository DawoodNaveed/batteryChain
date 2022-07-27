<?php

namespace App\Controller;

use App\Entity\Battery;
use App\Entity\BatteryReturn;
use App\Entity\Manufacturer;
use App\Entity\Recycler;
use App\Entity\User;
use App\Enum\RoleEnum;
use App\Form\BulkReturnFormType;
use App\Form\ReturnFormType;
use App\Helper\CustomHelper;
use App\Service\BatteryService;
use App\Service\ManufacturerService;
use App\Service\RecyclerService;
use App\Service\TransactionLogService;
use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ReturnController
 * @package App\Controller
 * @property Security security
 * @property EntityManagerInterface entityManager
 * @property ManufacturerService manufacturerService
 * @property BatteryService batteryService
 * @property RecyclerService recyclerService
 * @property TranslatorInterface translator
 * @property TransactionLogService transactionLogService
 */
class ReturnController extends CRUDController
{
    /**
     * RecyclerController constructor.
     * @param Security $security
     * @param EntityManagerInterface $entityManager
     * @param ManufacturerService $manufacturerService
     * @param BatteryService $batteryService
     * @param RecyclerService $recyclerService
     * @param TranslatorInterface $translator
     * @param TransactionLogService $transactionLogService
     */
    public function __construct(
        Security $security,
        EntityManagerInterface $entityManager,
        ManufacturerService $manufacturerService,
        BatteryService $batteryService,
        RecyclerService $recyclerService,
        TranslatorInterface $translator,
        TransactionLogService $transactionLogService
    ) {
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->manufacturerService = $manufacturerService;
        $this->batteryService = $batteryService;
        $this->recyclerService = $recyclerService;
        $this->translator = $translator;
        $this->transactionLogService = $transactionLogService;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function returnAction(Request $request): Response
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $manufacturer = null;

        if (!in_array(RoleEnum::ROLE_SUPER_ADMIN, $user->getRoles(), true) &&
            !in_array(RoleEnum::ROLE_ADMIN, $user->getRoles(), true) &&
            in_array(RoleEnum::ROLE_MANUFACTURER, $user->getRoles(), true) ) {
            $recyclers = $this->recyclerService->toChoiceArray($user->getManufacturer()->getRecyclers(), true);
            $manufacturer = $user->getManufacturer();
            $recyclers = array_merge([
                $manufacturer->getName() => $manufacturer
            ], $recyclers);
        } else {
            $recyclers = $this->recyclerService->getAllRecyclers();
            $recyclers = $this->recyclerService->toChoiceArray($recyclers, true);
        }

        $form = $this->createForm(ReturnFormType::class, null, [
            'recyclers' => $recyclers
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted())
        {
            if ($form->get('cancel')->isClicked()) {
                return new RedirectResponse($this->generateUrl('sonata_admin_dashboard'));
            }

            $formData = $form->getData();
            $serialNumber = $formData['battery'] ?? null;
            /** @var Recycler|null $recycler */
            $recycler = $formData['recycler'] ?? null;

            if (empty($serialNumber)) {
                $this->addFlash('sonata_flash_error', 'Kindly Insert Valid Battery Serial Number!');
                return new RedirectResponse($this->admin->generateUrl('list'));
            }

            /** @var Battery|null $battery */
            $battery = $this->batteryService->fetchBatteryBySerialNumber(
                $serialNumber,
                $manufacturer,
                $user->getManufacturer() ? false : true
            );

            if (empty($battery) || $battery->getStatus() === CustomHelper::BATTERY_STATUS_PRE_REGISTERED) {
                $this->addFlash('sonata_flash_error', 'Battery may not exist or registered!');
                return new RedirectResponse($this->admin->generateUrl('return'));
            }

            if (CustomHelper::BATTERY_STATUSES[$battery->getStatus()] >=
                CustomHelper::BATTERY_STATUSES[CustomHelper::BATTERY_STATUS_RETURNED] ||
                ($this->transactionLogService->isExist($battery, CustomHelper::BATTERY_STATUS_RETURNED))
            ) {
                $this->addFlash('sonata_flash_error', 'Battery is already returned!');

                return new RedirectResponse($this->admin->generateUrl('list'));
            }

            /** If Admin / Super Admin - we will use battery's manufacturer's User */
            if (in_array(RoleEnum::ROLE_SUPER_ADMIN, $user->getRoles(), true)) {
                $user = $battery->getManufacturer()->getUser();
            }

            $this->transactionLogService->createTransactionLog($battery, CustomHelper::BATTERY_STATUS_RETURNED);
            $return = new BatteryReturn();
            $return->setUpdated(new \DateTime('now'));
            $return->setCreated(new \DateTime('now'));
            $return->setAddress($formData['information']['address']);
            $return->setCity($formData['information']['city']);
            $return->setCountry($formData['information']['country']);
            $return->setReturnDate(new \DateTime('now'));
            $return->setReturnFrom($user);
            $return->setReturnTo($recycler instanceof Recycler ? $recycler : null);
            $return->setBattery($battery);
            $battery->setStatus(CustomHelper::BATTERY_STATUS_RETURNED);
            $battery->setCurrentPossessor($user);

            $this->entityManager->persist($return);
            $this->entityManager->flush();

            $this->addFlash('sonata_flash_success', 'Return Added Successfully!');

            return new RedirectResponse($this->admin->generateUrl('list'));
        }

        return $this->render(
            'return/add_single_return.html.twig',
            array(
                'form' => $form->createView(),
            )
        );
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function bulkReturnAction(Request $request): Response
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $manufacturers = null;

        // In-Case of Super Admin
        if (in_array(RoleEnum::ROLE_SUPER_ADMIN, $this->getUser()->getRoles(), true) ||
            in_array(RoleEnum::ROLE_ADMIN, $this->getUser()->getRoles(), true)) {
            $manufacturers = $this->manufacturerService->getManufactures($user, true);
        }

        if (!in_array(RoleEnum::ROLE_SUPER_ADMIN, $user->getRoles(), true) &&
            !in_array(RoleEnum::ROLE_ADMIN, $user->getRoles(), true) &&
            in_array(RoleEnum::ROLE_MANUFACTURER, $user->getRoles(), true) ) {
            $recyclers = $this->recyclerService->toChoiceArray($user->getManufacturer()->getRecyclers());
            $recyclers = array_merge([
                $user->getManufacturer()->getName() => $user->getManufacturer()
            ], $recyclers);
        } else {
            $recyclers = $this->recyclerService->getAllRecyclers();
            $recyclers = $this->recyclerService->toChoiceArray($recyclers);
        }

        $form = $this->createForm(BulkReturnFormType::class, null, [
            'manufacturer' => $manufacturers,
            'recyclers' => $recyclers
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('cancel')->isClicked()) {
                return new RedirectResponse($this->generateUrl('sonata_admin_dashboard'));
            }

            $formData = $form->getData();
            /** @var Manufacturer $manufacturer */
            $manufacturer = $formData['manufacturer'] ?? null;

            if (!empty($manufacturer)) {
                $manufacturer = $this->manufacturerService->manufacturerRepository->findOneBy([
                    'id' => $manufacturer
                ]);
            }
            /** @var Recycler|null $recycler */
            $recycler = $formData['recycler'] ?? null;

            if (empty($recycler)) {
                $this->addFlash('sonata_flash_error', 'Kindly provide Recycler!');
                return new RedirectResponse($this->admin->generateUrl('bulkReturn'));
            } else {
                // if battery return to manufacturer
                if ($recycler instanceof Manufacturer) {
                    $recycler = null;
                } else {
                    $recycler = $this->recyclerService->recyclerRepository->findOneBy([
                        'id' => $recycler
                    ]);
                }
            }

            $file = $request->files->all();

            if (!empty($file) && isset($file['bulk_return_form']['csv'])) {
                /** @var UploadedFile $file */
                $file = $file['bulk_return_form']['csv'];
                $validCsv = $this->batteryService->isValidCsv($file);
                if ($validCsv['error'] == 0) {
                    if (!empty($manufacturer)) {
                        $user = $manufacturer->getUser();
                    }

                    $addReturns = $this->batteryService->extractCsvAndAddReturns($file, $user, $recycler);

                    if (!empty($addReturns) && !empty($addReturns['error'])) {
                        foreach ($addReturns['error'] as $error) {
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
            'return/bulk_returns.html.twig',
            array(
                'form' => $form->createView(),
            )
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getRecyclerByManufacturerAction(Request $request): JsonResponse
    {
        $manufacturer = $request->request->get('bulk_return_form');
        /** @var Manufacturer|null $manufacturer */
        $manufacturer = $this->manufacturerService->manufacturerRepository->findOneBy(['id' => $manufacturer['manufacturer']]);
        /** @var Recycler[] $recyclers */
        $recyclers = $manufacturer->getRecyclers();

        return new JsonResponse([
            'recyclers' => $this->recyclerService->toChoiceArray($recyclers)
        ]);
    }
}