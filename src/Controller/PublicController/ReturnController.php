<?php

namespace App\Controller\PublicController;

use App\Entity\Battery;
use App\Entity\Recycler;
use App\Form\ReturnPublicFormType;
use App\Helper\CustomHelper;
use App\Service\BatteryReturnService;
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
 * @property BatteryReturnService returnService
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
     * @param BatteryReturnService $returnService
     */
    public function __construct(
        Security $security,
        EntityManagerInterface $entityManager,
        ManufacturerService $manufacturerService,
        BatteryService $batteryService,
        RecyclerService $recyclerService,
        CountryService $countryService,
        TranslatorInterface $translator,
        BatteryReturnService $returnService
    ) {
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->manufacturerService = $manufacturerService;
        $this->batteryService = $batteryService;
        $this->recyclerService = $recyclerService;
        $this->translator = $translator;
        $this->countryService = $countryService;
        $this->returnService = $returnService;
    }

    /**
     * @Route(path="battery/return/{slug}", name="add_return")
     * @param Request $request
     * @param $slug
     * @return Response
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function returnAction(Request $request, $slug): Response
    {
        /** @var Battery|null $battery */
        $battery = $this->batteryService->fetchBatteryBySerialNumber($slug);
        $isFallback = false;

        if (empty($battery)) {
            $this->addFlash('danger', $this->translator->trans('Kindly provide valid url!'));
            return new RedirectResponse($this->generateUrl('homepage'));
        }

        $country = $this->countryService->getCountryByName('Switzerland');
        $recyclers = $this->recyclerService->fetchManufacturerRecyclersByCountry(
            $battery->getManufacturer(),
            $country
        );

        /** Fallback */
        if (empty($recyclers)) {
            $recyclers = $this->recyclerService->fetchFallbackRecyclersByCountry($country);
            $isFallback = true;
        }

        $countries = $this->countryService->getCountries();
        $form = $this->createForm(ReturnPublicFormType::class, null, [
            'countries' => $countries,
            'default_country' => $country->getId(),
            'recyclers' => $this->recyclerService->toChoiceArray($recyclers),
            'fall_back' => $isFallback
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted())
        {
            if ($form->get('cancel')->isClicked()) {
                return new RedirectResponse($this->generateUrl('homepage'));
            }

            $formData = $form->getData();
            $recyclerId = $formData['recyclerId'] ?? null;

            // Fallback if user doesn't change dropdown
            if (empty($recyclerId)) {
                $recyclerId = $formData['recyclers'] ?? null;
            }

            if (empty($recyclerId)) {
                $this->addFlash('danger', 'Kindly Select Recycler!');
                return new RedirectResponse($this->generateUrl('homepage'));
            }

            /** @var Recycler $recycler */
            $recycler = $this->recyclerService->getRecyclerByIds([$recyclerId])[0];

            if (empty($formData['fallback']) && empty($formData['information']['contact']) && empty($formData['information']['email'])) {
                $this->addFlash('danger', 'Kindly Provide Email or Contact Information!');
                return new RedirectResponse($this->generateUrl('add_return', [
                    'slug' => $slug
                ]));
            }

            if (empty($formData['fallback']) && empty($formData['information']['name'])) {
                $this->addFlash('danger', 'Kindly Provide User Information!');
                return new RedirectResponse($this->generateUrl('add_return', [
                    'slug' => $slug
                ]));
            }

            // If fallback Recycler, user details will be his own
            if ($formData['fallback'] == 1) {
                $formData['information']['name'] = $recycler->getName();
                $formData['information']['email'] = $recycler->getEmail();
                $formData['information']['contact'] = $recycler->getContact();
            }

            $battery->setStatus(CustomHelper::BATTERY_STATUS_RETURNED);
            $this->returnService
                ->createReturn(
                    $battery->getManufacturer()->getUser(),
                    $battery,
                    $recycler
                );

            $this->recyclerService
                ->sendNewBatteryReturnEmail(
                    $recycler,
                    $battery,
                    $formData,
                    $this->generateUrl('battery_detail', [
                        'search' => $battery->getSerialNumber()
                    ], 0)
                );
            $this->addFlash('success', 'Return Added Successfully!');

            return new RedirectResponse($this->generateUrl('battery_detail', [
                'search' => $battery->getSerialNumber()
            ]));
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