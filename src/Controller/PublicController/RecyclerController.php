<?php

namespace App\Controller\PublicController;

use App\Entity\Country;
use App\Service\BatteryService;
use App\Service\CountryService;
use App\Service\ManufacturerService;
use App\Service\RecyclerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class RecyclerController
 * @package App\Controller\PublicController
 * @property Security security
 * @property EntityManagerInterface entityManager
 * @property ManufacturerService manufacturerService
 * @property BatteryService batteryService
 * @property RecyclerService recyclerService
 * @property TranslatorInterface translator
 * @property CountryService countryService
 */
class RecyclerController extends AbstractController
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
     * @Route(path="/recyclers/country/{id}", name="country_recyclers")
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function getRecyclerAction(Request $request, $id): JsonResponse
    {
        $isFallback = false;
        $serialNumber = $request->request->get('serial_number');
        $battery = $this->batteryService->batteryRepository->findOneBy([
            'internalSerialNumber' => $serialNumber
        ]);
        /** @var Country|null $country */
        $country = $this->countryService->countryRepository->findOneBy(['id' => $id]);
        $recyclers = $this->recyclerService->fetchManufacturerRecyclersByCountry(
            $battery->getManufacturer(),
            $country
        );

        /** Fallback */
        if (empty($recyclers)) {
            $recyclers = $this->recyclerService->fetchFallbackRecyclersByCountry($country);
            $isFallback = true;
        }

        $details = null;

        //only for fallback recycler if available
        if (!empty($recyclers[0]) && $isFallback) {
            $id = $recyclers[0]->id;
            $details = [
                'email' => $recyclers[0]->email,
                'name' => $recyclers[0]->name,
                'contact' => $recyclers[0]->contact,
                'address' => $recyclers[0]->address,
                'postalCode' => $recyclers[0]->postal_code,
                'city' => $recyclers[0]->city,
            ];
        } elseif (!empty($recyclers[0]) && $isFallback === false) {
            $id = $recyclers[0]->getId();
        }


        return new JsonResponse([
            'recyclers' => $this->recyclerService->toChoiceArray($recyclers),
            'fall_back' => $isFallback,
            'details' => $details,
            'id' => $id
        ]);
    }

    /**
     * @Route(path="/recyclers/{id}", name="recycler_id")
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function getRecyclerByIdAction(Request $request, $id): JsonResponse
    {
        $recycler = $this->recyclerService->getRecyclerById($id);

        if (empty($recycler)) {
            return new JsonResponse([
                'status' => false
            ]);
        }

        return new JsonResponse([
            'status' => true,
            'name' => $recycler->getName(),
            'email' => $recycler->getEmail(),
            'contact' => $recycler->getContact(),
            'address' => $recycler->getAddress(),
            'postalCode' => $recycler->getPostalCode(),
            'city' => $recycler->getCity(),
        ]);
    }
}