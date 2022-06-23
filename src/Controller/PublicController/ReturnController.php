<?php

namespace App\Controller\PublicController;

use App\Entity\Battery;
use App\Entity\Recycler;
use App\Form\ReturnPublicFormType;
use App\Helper\CustomHelper;
use App\Service\BatteryService;
use App\Service\CountryService;
use App\Service\ManufacturerService;
use App\Service\RecyclerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ReturnController
 * @package App\Controller\PublicController
 * @property Security security
 * @property EntityManagerInterface entityManager
 * @property ManufacturerService manufacturerService
 * @property BatteryService batteryService
 * @property RecyclerService recyclerService
 * @property TranslatorInterface translator
 * @property CountryService countryService
 */
class ReturnController extends AbstractController
{
    /**
     * RecyclerController constructor.
     * @param Security $security
     * @param EntityManagerInterface $entityManager
     * @param ManufacturerService $manufacturerService
     * @param BatteryService $batteryService
     * @param RecyclerService $recyclerService
     * @param CountryService $countryService
     * @param TranslatorInterface $translator
     */
    public function __construct(
        Security $security,
        EntityManagerInterface $entityManager,
        ManufacturerService $manufacturerService,
        BatteryService $batteryService,
        RecyclerService $recyclerService,
        CountryService $countryService,
        TranslatorInterface $translator
    ) {
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->manufacturerService = $manufacturerService;
        $this->batteryService = $batteryService;
        $this->recyclerService = $recyclerService;
        $this->translator = $translator;
        $this->countryService = $countryService;
    }

    /**
     * @Route(path="/addReturn/{slug}", name="add_return")
     * @param Request $request
     * @return Response
     */
    public function returnAction(Request $request, $slug): Response
    {
        $countries = $this->countryService->getCountries();
        $form = $this->createForm(ReturnPublicFormType::class, null, [
            'countries' => $countries
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted())
        {
            if ($form->get('cancel')->isClicked()) {
                return new RedirectResponse($this->generateUrl('homepage'));
            }

            $formData = $form->getData();
            $serialNumber = $slug;
            $recyclerId = $formData['recyclerId'] ?? null;

            if (empty($recyclerId)) {
                $this->addFlash('danger', 'Kindly Select Recycler!');
                return new RedirectResponse($this->generateUrl('homepage'));
            }

            /** @var Recycler $recycler */
            $recycler = $this->recyclerService->getRecyclerByIds([$recyclerId])[0];
            /** @var Battery|null $battery */
            $battery = $this->batteryService->fetchBatteryBySerialNumber($serialNumber);

            if (empty($formData['information']['name']) || empty($formData['information']['email'])) {
                $this->addFlash('danger', 'Kindly Provide User Information!');
                return new RedirectResponse($this->generateUrl('homepage'));
            }

            if (CustomHelper::BATTERY_STATUSES[$battery->getStatus()] >=
                CustomHelper::BATTERY_STATUSES[CustomHelper::BATTERY_STATUS_RETURNED]) {
                $this->addFlash('danger', 'Battery is already returned!');

                return new RedirectResponse($this->generateUrl('homepage'));
            }

            //mail - to - Recycler

            $this->addFlash('success', 'Return Added Successfully!');

            return new RedirectResponse($this->generateUrl('homepage'));
        }

        return $this->render(
            'public_templates/battery_return/add_battery_return.html.twig',
            array(
                'form' => $form->createView(),
                'serialNumber' => $slug
            )
        );
    }
}