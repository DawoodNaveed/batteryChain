<?php

namespace App\Controller;

use App\Entity\Country;
use App\Entity\Manufacturer;
use App\Entity\User;
use App\Enum\RoleEnum;
use App\Form\AvailableRecyclerFormType;
use App\Form\BulkUpdateRecyclerFormType;
use App\Service\BatteryService;
use App\Service\CountryService;
use App\Service\CsvService;
use App\Service\ManufacturerService;
use App\Service\RecyclerService;
use App\Service\UserService;
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
 * Class RecyclerController
 * @package App\Controller
 * @property CountryService countryService
 * @property RecyclerService recyclerService
 * @property Security security
 * @property EntityManagerInterface entityManager
 * @property ManufacturerService manufacturerService
 * @property UserService $userService
 * @property BatteryService batteryService
 * @property TranslatorInterface translator
 * @property CsvService csvService
 */
class RecyclerController extends CRUDController
{
    /**
     * RecyclerController constructor.
     * @param CountryService $countryService
     * @param RecyclerService $recyclerService
     * @param Security $security
     * @param EntityManagerInterface $entityManager
     * @param ManufacturerService $manufacturerService
     * @param UserService $userService
     * @param BatteryService $batteryService
     * @param TranslatorInterface $translator
     * @param CsvService $csvService
     */
    public function __construct(
        CountryService $countryService,
        RecyclerService $recyclerService,
        Security $security,
        EntityManagerInterface $entityManager,
        ManufacturerService $manufacturerService,
        UserService $userService,
        BatteryService $batteryService,
        TranslatorInterface $translator,
        CsvService $csvService
    ) {
        $this->countryService = $countryService;
        $this->recyclerService = $recyclerService;
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->manufacturerService = $manufacturerService;
        $this->userService = $userService;
        $this->batteryService = $batteryService;
        $this->translator = $translator;
        $this->csvService = $csvService;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function attachAction(Request $request): Response
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $manufacturers = null;

        // In-Case Super Admin
        if (in_array(RoleEnum::ROLE_SUPER_ADMIN, $this->getUser()->getRoles(), true)) {
            $manufacturers = $this->manufacturerService->getManufactures($user);
        }

        $countries = $this->countryService->getCountries();
        $recyclers = $this->recyclerService->getRecyclersByCountryId(array_values($countries)[0]);
        $form = $this->createForm(AvailableRecyclerFormType::class, null, [
            'countries' => $countries,
            'recyclers' => $recyclers,
            'manufacturers' => $manufacturers
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            /** @var Manufacturer $manufacturer */
            $manufacturer = $formData['manufacturers'] ?? null;
            $recyclers = $this->recyclerService->getRecyclerByIds($formData['recycler']);

            if (empty($manufacturer)) {
                $manufacturer = $user->getManufacturer();
            }

            foreach ($recyclers as $recycler) {
                $manufacturer->addRecycler($recycler);
            }

            $this->entityManager->flush();
            return new RedirectResponse($this->admin->generateUrl('list'));
        }

        return $this->render(
            'attach_recyclers.html.twig',
            array(
                'form' => $form->createView(),
            )
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getRecyclerAction(Request $request): JsonResponse
    {
        $country = $request->request->get('available_recycler_form');
        /** @var Country|null $country */
        $country = $this->countryService->countryRepository->findOneBy(['id' => $country['country']]);
        $recyclers = $this->recyclerService->getRecyclers($country);

        return new JsonResponse([
            'recyclers' => $this->recyclerService->toChoiceArray($recyclers)
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function bulkUpdateAction(Request $request): Response
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $countries = $this->countryService->getCountries(true);

        $form = $this->createForm(BulkUpdateRecyclerFormType::class, null, [
            'regions' => $countries
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $country = $formData['region'];
            $file = $request->files->all();

            if (!empty($file) && isset($file['bulk_update_recycler_form']['csv'])) {
                /** @var UploadedFile $file */
                $file = $file['bulk_update_recycler_form']['csv'];
                $validCsv = $this->batteryService->isValidCsv($file);

                if ($validCsv['error'] == 0) {
                    $createOrUpdateRecycler = $this->recyclerService
                        ->extractCsvAndUpdateRecyclers(
                            $file,
                            $user->getManufacturer() ?? null,
                            $country
                        );

                    if (!empty($createOrUpdateRecycler) && !empty($createOrUpdateRecycler['error'])) {
                        $this->addFlash('error', $this->translator->trans($createOrUpdateRecycler['message']));
                    } else {
                        $this->addFlash('success', $this->translator->trans('service.success.recycler_added_successfully'));

                        if (isset($createOrUpdateRecycler['total']) && isset($createOrUpdateRecycler['failure'])
                            && $createOrUpdateRecycler['failure'] !== 0) {
                            $this->addFlash(
                                'warning',
                                $this->translator->trans(
                                    'service.success.recycler_bulk_status',
                                    [
                                        '%failure_recyclers%' => $createOrUpdateRecycler['failure'],
                                        '%total_recyclers%' => $createOrUpdateRecycler['total']
                                    ]
                                )
                            );
                        }
                    }
                } else {
                    $this->addFlash('error', $this->translator->trans($validCsv['message']));
                }

                return new RedirectResponse($this->admin->generateUrl('list'));
            }
        }

        return $this->render(
            'recycler/bulk_update_recyclers.html.twig',
            array(
                'form' => $form->createView(),
            )
        );
    }

    /**
     * @return RedirectResponse
     */
    public function downloadRecyclersAction(): RedirectResponse
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if ($user->getManufacturer()) {
            $filename = 'Recyclers' . ' | ' . $user->getManufacturer()->getName() . '.csv';
            $recyclers = $this->recyclerService
                ->recyclerRepository
                ->getRecyclersByManufacturer($user->getManufacturer());
            $this->csvService->downloadRecyclersCsv($recyclers, $filename);
            exit();
        }

        return $this->redirectToList();
    }

//Will remove it in future
//    /**
//     * @param Request $request
//     * @return Response
//     * @throws NotFoundExceptionInterface
//     * @throws ContainerExceptionInterface
//     */
//    public function deleteAction(Request $request): Response
//    {
//        /** @var FlashBagInterface $flashBag */
//        $flashBag = $this->container->get('session')->getFlashBag();
//        $id = $request->get($this->admin->getIdParameter());
//        /** @var Recycler $recycler */
//        $recycler = $this->admin->getObject($id);
//        /** @var User $user */
//        $user = $this->security->getUser();
//
//        // In-Case Super Admin
//        if (in_array(RoleEnum::ROLE_SUPER_ADMIN, $this->getUser()->getRoles(), true)) {
//            $recycler->removeAllManufacturers();
//            $flashBag->set(
//                'sonata_flash_success',
//                'Recycler De-attached with All Manufacturer!'
//            );
//        } else {
//            $recycler->removeManufacturer($user->getManufacturer());
//            $flashBag->set(
//                'sonata_flash_success',
//                'Recycler De-attached Successfully!'
//            );
//        }
//
//        $this->entityManager->remove($recycler);
//        $this->entityManager->flush();
//
//        return new RedirectResponse($this->admin->generateUrl('list'));
//    }
}