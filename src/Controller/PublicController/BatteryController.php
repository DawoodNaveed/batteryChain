<?php

namespace App\Controller\PublicController;

use App\Entity\Battery;
use App\Form\ReportBatteryReturnFormType;
use App\Helper\CustomHelper;
use App\Service\BatteryService;
use App\Service\CountryService;
use App\Service\ManufacturerService;
use App\Service\RecyclerService;
use App\Service\TransactionLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class BatteryController
 * @package App\Controller\PublicController
 * @property Security security
 * @property EntityManagerInterface entityManager
 * @property ManufacturerService manufacturerService
 * @property BatteryService batteryService
 * @property RecyclerService recyclerService
 * @property TranslatorInterface translator
 * @property CountryService countryService
 * @property TransactionLogService transactionLogService
 */
class BatteryController extends AbstractController
{
    /**
     * BatteryController constructor.
     * @param EntityManagerInterface $entityManager
     * @param BatteryService $batteryService
     * @param RecyclerService $recyclerService
     * @param TranslatorInterface $translator
     * @param TransactionLogService $transactionLogService
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        BatteryService $batteryService,
        RecyclerService $recyclerService,
        TranslatorInterface $translator,
        TransactionLogService $transactionLogService
    ) {
        $this->entityManager = $entityManager;
        $this->batteryService = $batteryService;
        $this->recyclerService = $recyclerService;
        $this->translator = $translator;
        $this->transactionLogService = $transactionLogService;
    }

    /**
     * @Route(path="battery/detail", name="battery_detail")
     * @param Request $request
     * @return Response
     */
    public function getBatteryDetails(Request $request): Response
    {
        /** @var Battery|null $battery */
        $battery = $this->batteryService
            ->fetchBatteryBySerialNumber($request->get('search'));

        if (empty($battery)) {
            $this->addFlash('danger', 'Kindly provide valid url query!');
            return new RedirectResponse('/');
        }

        return $this->render(
            'public_templates/detail_view.html.twig', [
                'battery' => $battery
            ]
        );
    }

    /**
     * @Route(path="battery/report/return/{slug}", name="report_battery_return")
     * @param Request $request
     * @param $slug
     * @return Response
     */
    public function reportBatteryReturnAction(Request $request, $slug): Response
    {
        /** @var Battery|null $battery */
        $battery = $this->batteryService->fetchBatteryBySerialNumber($slug);

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
        $this->entityManager->flush();
        $this->addFlash('success', $this->translator->trans('Report Added Successfully!'));

        return new RedirectResponse($this->generateUrl('battery_detail', [
            'search' => $battery->getSerialNumber()
        ]));
    }
}