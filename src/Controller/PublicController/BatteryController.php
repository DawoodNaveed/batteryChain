<?php

namespace App\Controller\PublicController;

use App\Entity\Battery;
use App\Helper\CustomHelper;
use App\Service\BatteryService;
use App\Service\CountryService;
use App\Service\EncryptionService;
use App\Service\ManufacturerService;
use App\Service\RecyclerService;
use App\Service\TransactionLogService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class BatteryController
 * @package App\Controller\PublicController
 * @property UserService userService
 * @property UrlGeneratorInterface urlGenerator
 * @property Security security
 * @property EntityManagerInterface entityManager
 * @property ManufacturerService manufacturerService
 * @property BatteryService batteryService
 * @property RecyclerService recyclerService
 * @property TranslatorInterface translator
 * @property CountryService countryService
 * @property TransactionLogService transactionLogService
 * @property EncryptionService encryptionService
 */
class BatteryController extends AbstractController
{
    /**
     * BatteryController constructor.
     * @param UserService $userService
     * @param UrlGeneratorInterface $urlGenerator
     * @param EntityManagerInterface $entityManager
     * @param BatteryService $batteryService
     * @param RecyclerService $recyclerService
     * @param TranslatorInterface $translator
     * @param TransactionLogService $transactionLogService
     * @param EncryptionService $encryptionService
     */
    public function __construct(
        UserService $userService,
        UrlGeneratorInterface $urlGenerator,
        EntityManagerInterface $entityManager,
        BatteryService $batteryService,
        RecyclerService $recyclerService,
        TranslatorInterface $translator,
        TransactionLogService $transactionLogService,
        EncryptionService $encryptionService
    ) {
        $this->userService = $userService;
        $this->urlGenerator = $urlGenerator;
        $this->entityManager = $entityManager;
        $this->batteryService = $batteryService;
        $this->recyclerService = $recyclerService;
        $this->translator = $translator;
        $this->transactionLogService = $transactionLogService;
        $this->encryptionService = $encryptionService;
    }

    /**
     * @Route(path="battery/detail", name="battery_detail")
     * @param Request $request
     * @return Response
     */
    public function getBatteryDetails(Request $request): Response
    {
        /** If Logged In - redirect to Dashboard */
        if ($this->userService->isAuthenticated()) {
            return new RedirectResponse($this->urlGenerator->generate('home'));
        }

        $decryptedNumber = $this->encryptionService->validateAndFetchSerialNumber(
            $this->encryptionService->decryptString($request->get('search'))
        );

        if ($decryptedNumber !== false) {
            /** @var Battery[]|null $battery */
            $batteries = $this->batteryService
                ->batteryRepository->findBatteriesByBothSerialNumber($decryptedNumber);

            if (empty($batteries)) {
                $this->addFlash('danger', 'Kindly provide valid url query!');
                return new RedirectResponse('/');
            }

            if (count($batteries) > 1) {
                return $this->render(
                    'public_templates/battery_list_view.html.twig', [
                        'batteries' => $batteries
                    ]
                );
            }

            if (count($batteries) == 1 && isset($batteries[0])) {
                /** @var Battery $battery */
                $battery = $batteries[0];
            }
            
            $deliveryCounter = 0;
            foreach ($battery->getTransactionLogs()->toArray() as $transaction) {
                if ($transaction->getTransactionType() === CustomHelper::BATTERY_STATUS_DELIVERED) {
                    $deliveryCounter++;
                }
            }

            return $this->render(
                'public_templates/detail_view.html.twig', [
                    'battery' => $battery,
                    'detail' => isset(CustomHelper::BATTERY_STATUSES_DETAILS[$battery->getStatus()])
                        ? $this->translator->trans(CustomHelper::BATTERY_STATUSES_DETAILS[$battery->getStatus()])
                        : null,
                    'slug' => $this->encryptionService->encryptString($battery->getInternalSerialNumber()),
                    'transactions' => $battery->getTransactionLogs()->toArray(),
                    'deliveryCounter' => $deliveryCounter
                ]
            );
        }

        $this->addFlash('danger', 'Kindly provide valid url query!');
        return new RedirectResponse($this->generateUrl('homepage'));
    }

    /**
     * @Route(path="battery/detail/{id}", name="battery_detail_id")
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function getBatteryDetailsById(Request $request, $id): Response
    {
        /** If Logged In - redirect to Dashboard */
        if ($this->userService->isAuthenticated()) {
            return new RedirectResponse($this->urlGenerator->generate('home'));
        }

        return new RedirectResponse($this->generateUrl('battery_detail', [
            'search' => $this->encryptionService->encryptString($id)
        ]));
    }

    /**
     * @Route(path="battery/report/return/{slug}", name="report_battery_return")
     * @param Request $request
     * @param $slug
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function reportBatteryReturnAction(Request $request, $slug): Response
    {
        /** If Logged In - redirect to Dashboard */
        if ($this->userService->isAuthenticated()) {
            return new RedirectResponse($this->urlGenerator->generate('home'));
        }

        $decryptedNumber = $this->encryptionService->validateAndFetchSerialNumber(
            $this->encryptionService->decryptString($slug)
        );

        if ($decryptedNumber !== false) {
            /** @var Battery|null $battery */
            $battery = $this
                ->batteryService
                ->batteryRepository
                ->findBatteryByInternalSerialNumber($decryptedNumber);

            if (empty($battery)) {
                $this->addFlash('danger', $this->translator->trans('Kindly provide valid url!'));
                return new RedirectResponse($this->generateUrl('homepage'));
            }

            if ((CustomHelper::BATTERY_STATUSES[$battery->getStatus()] >=
                    CustomHelper::BATTERY_STATUSES[CustomHelper::BATTERY_STATUS_RECYCLED]) ||
                ($this->transactionLogService->isExist($battery, CustomHelper::BATTERY_STATUS_RECYCLED))) {
                $this->addFlash('danger', $this->translator->trans('Battery is already recycled!'));
                return new RedirectResponse($this->generateUrl('battery_detail', [
                    'search' => $slug
                ]));
            }

            $this->transactionLogService->createTransactionLog($battery, CustomHelper::BATTERY_STATUS_RECYCLED);
            $battery->setStatus(CustomHelper::BATTERY_STATUS_RECYCLED);
            $battery->setUpdated(new \DateTime('now'));
            $this->entityManager->flush();
            $this->addFlash('success', $this->translator->trans('Report Added Successfully!'));

            return new RedirectResponse($this->generateUrl('battery_detail', [
                'search' => $slug
            ]));
        }

        $this->addFlash('danger', 'Kindly provide valid url query!');
        return new RedirectResponse($this->generateUrl('homepage'));
    }

    /**
     * @Route(path="battery/qr/result", name="battery_qr_result")
     * @param Request $request
     * @return Response
     */
    public function getBatteryDetailsByScan(Request $request): Response
    {
        /** If Logged In - redirect to Dashboard */
        if ($this->userService->isAuthenticated()) {
            return new RedirectResponse($this->urlGenerator->generate('home'));
        }

        if (empty($request->get('search'))) {
            $this->addFlash('danger', 'Kindly provide serial number');
            return new RedirectResponse($this->generateUrl('scan_qr'));
        }

        return new RedirectResponse(
            $this->generateUrl('battery_detail', [
                'search' => $this->encryptionService->encryptString($request->get('search'))
            ])
        );
    }
}