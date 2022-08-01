<?php

namespace App\Controller\PublicController;

use App\Entity\Battery;
use App\Entity\Recycler;
use App\Form\ReturnPublicFormType;
use App\Helper\CustomHelper;
use App\Service\BatteryReturnService;
use App\Service\BatteryService;
use App\Service\CountryService;
use App\Service\EncryptionService;
use App\Service\ManufacturerService;
use App\Service\RecyclerService;
use App\Service\TransactionLogService;
use App\Service\UserService;
use Doctrine\DBAL\Driver\Exception;
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
 * Class ReturnController
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
 * @property BatteryReturnService returnService
 * @property TransactionLogService transactionLogService
 * @property EncryptionService encryptionService
 */
class ReturnController extends AbstractController
{
    /**
     * RecyclerController constructor.
     * @param UserService $userService
     * @param UrlGeneratorInterface $urlGenerator
     * @param Security $security
     * @param EntityManagerInterface $entityManager
     * @param ManufacturerService $manufacturerService
     * @param BatteryService $batteryService
     * @param RecyclerService $recyclerService
     * @param CountryService $countryService
     * @param TranslatorInterface $translator
     * @param BatteryReturnService $returnService
     * @param TransactionLogService $transactionLogService
     * @param EncryptionService $encryptionService
     */
    public function __construct(
        UserService $userService,
        UrlGeneratorInterface $urlGenerator,
        Security $security,
        EntityManagerInterface $entityManager,
        ManufacturerService $manufacturerService,
        BatteryService $batteryService,
        RecyclerService $recyclerService,
        CountryService $countryService,
        TranslatorInterface $translator,
        BatteryReturnService $returnService,
        TransactionLogService $transactionLogService,
        EncryptionService $encryptionService
    ) {
        $this->userService = $userService;
        $this->urlGenerator = $urlGenerator;
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->manufacturerService = $manufacturerService;
        $this->batteryService = $batteryService;
        $this->recyclerService = $recyclerService;
        $this->translator = $translator;
        $this->countryService = $countryService;
        $this->returnService = $returnService;
        $this->transactionLogService = $transactionLogService;
        $this->encryptionService = $encryptionService;
    }

    /**
     * @Route(path="battery/return/{slug}", name="add_return")
     * @param Request $request
     * @param $slug
     * @return Response
     * @throws Exception
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function returnAction(Request $request, $slug): Response
    {
        /** If Logged In - redirect to Dashboard */
        if ($this->userService->isAuthenticated()) {
            return new RedirectResponse($this->urlGenerator->generate('home'));
        }

        $decryptedNumber = $this->encryptionService->validateAndFetchSerialNumber(
            $this->encryptionService->decryptString($slug)
        );

        if ($decryptedNumber === false) {
            $this->addFlash('danger', 'Kindly provide valid url query!');
            return new RedirectResponse($this->generateUrl('homepage'));
        }

        /** @var Battery|null $battery */
        $battery = $this->batteryService
            ->fetchBatteryBySerialNumber($decryptedNumber);

        if (empty($battery)) {
            $this->addFlash('danger', 'Kindly provide valid url query!');
            return new RedirectResponse('/');
        }

        if ((CustomHelper::BATTERY_STATUSES[$battery->getStatus()] >=
                CustomHelper::BATTERY_STATUSES[CustomHelper::BATTERY_STATUS_RETURNED]) ||
            ($this->transactionLogService->isExist($battery, CustomHelper::BATTERY_STATUS_RETURNED))) {
            $this->addFlash('danger', $this->translator->trans('Battery is already returned!'));
            return new RedirectResponse($this->generateUrl('battery_detail', [
                'search' => $slug
            ]));
        }

        $isFallback = false;
        $response = CustomHelper::get_ip_address();

        if (empty($response)) {
            $response = CustomHelper::sendCurlRequestToGetIp(Request::METHOD_GET, ['Content-Type: application/json']);

            if (!empty($response->ip)) {
                $response = $response->ip;
            }
        }

        $details = CustomHelper::get_ip_details($response);

        if (!empty($details) && $details['country_code'] !== 'xx' && $details['country_code'] !== 'XX') {
            $country = $this->countryService->getCountryByCode($details['country_code']);
        }

        if (empty($country)) {
            $country = $this->countryService->getCountryByName('Switzerland');
        }

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
                return new RedirectResponse($this->generateUrl('battery_detail', [
                    'search' => $slug
                ]));
            }

            $formData = $form->getData();
            $recyclerId = $formData['recyclerId'] ?? null;

            // Fallback if user doesn't change dropdown
            if (empty($recyclerId)) {
                $recyclerId = $formData['recyclers'] ?? null;
            }

            if (empty($recyclerId)) {
                $this->addFlash('danger', 'Kindly Select Recycler!');
                return new RedirectResponse($this->generateUrl('add_return', [
                    'slug' => $slug
                ]));
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

            $transactionLog = $this->transactionLogService->createTransactionLog($battery, CustomHelper::BATTERY_STATUS_RETURNED);
            $battery->setStatus(CustomHelper::BATTERY_STATUS_RETURNED);
            $battery->setUpdated(new \DateTime('now'));
            $this->returnService
                ->createReturn(
                    $battery->getManufacturer()->getUser(),
                    $battery,
                    $recycler,
                    $transactionLog
                );

            $this->recyclerService
                ->sendNewBatteryReturnEmail(
                    $recycler,
                    $battery,
                    $formData,
                    $this->generateUrl('battery_detail', [
                        'search' => $slug
                    ], 0)
                );
            $this->addFlash('success', 'Return Added Successfully!');

            return new RedirectResponse($this->generateUrl('battery_detail', [
                'search' => $slug
            ]));
        }

        return $this->render(
            'public_templates/battery_return/add_battery_return.html.twig',
            array(
                'form' => $form->createView(),
                'serialNumber' => $battery->getSerialNumber(),
                'recycler' => (!empty($recyclers[0]) && $isFallback === true) ? $recyclers[0] : null,
                'fallBack' => $isFallback
            )
        );
    }
}