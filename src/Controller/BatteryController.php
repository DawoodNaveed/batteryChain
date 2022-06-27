<?php

namespace App\Controller;

use App\Entity\Battery;
use App\Entity\Manufacturer;
use App\Entity\User;
use App\Enum\RoleEnum;
use App\Form\BatteryDetailFormType;
use App\Form\BulkImportBatteryFormType;
use App\Service\BatteryService;
use App\Service\BatteryTypeService;
use App\Service\ManufacturerService;
use App\Service\PdfService;
use App\Service\UserService;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class BatteryController
 * @package App\Controller
 * @property BatteryService batteryService
 * @property TranslatorInterface translator
 * @property Security security
 * @property UserService userService
 * @property ManufacturerService manufacturerService
 * @property BatteryTypeService batteryTypeService
 * @property Environment twig
 * @property PdfService pdfService
 * @property string kernelProjectDir
 */
class BatteryController extends CRUDController
{
    /**
     * BatteryController constructor.
     * @param string $kernelProjectDir
     * @param BatteryService $batteryService
     * @param TranslatorInterface $translator
     * @param Security $security
     * @param UserService $userService
     * @param ManufacturerService $manufacturerService
     * @param BatteryTypeService $batteryTypeService
     * @param Environment $twig
     * @param PdfService $pdfService
     */
    public function __construct(
        string $kernelProjectDir,
        BatteryService $batteryService,
        TranslatorInterface $translator,
        Security $security,
        UserService $userService,
        ManufacturerService $manufacturerService,
        BatteryTypeService $batteryTypeService,
        Environment $twig,
        PdfService $pdfService
    ) {
        $this->batteryService = $batteryService;
        $this->translator = $translator;
        $this->security = $security;
        $this->userService = $userService;
        $this->manufacturerService = $manufacturerService;
        $this->batteryTypeService = $batteryTypeService;
        $this->twig = $twig;
        $this->pdfService = $pdfService;
        $this->kernelProjectDir = $kernelProjectDir;
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception|DBALException
     */
    public function importAction(Request $request): Response
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $manufacturers = null;

        // In-Case of Super Admin
        if (in_array(RoleEnum::ROLE_SUPER_ADMIN, $this->getUser()->getRoles(), true)) {
            $manufacturers = $this->manufacturerService->getManufactures($user);
        }

        $form = $this->createForm(BulkImportBatteryFormType::class, null, [
            'manufacturer' => $manufacturers
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            /** @var Manufacturer $manufacturer */
            $manufacturer = $formData['manufacturer'] ?? null;
            $file = $request->files->all();

            if (!empty($file) && isset($file['bulk_import_battery_form']['csv'])) {
                /** @var UploadedFile $file */
                $file = $file['bulk_import_battery_form']['csv'];
                $validCsv = $this->batteryService->isValidCsv($file);
                if ($validCsv['error'] == 0) {
                    if (empty($manufacturer)) {
                        $manufacturerId = $this->userService->getManufacturerId($user);
                    } else {
                        $manufacturerId = $manufacturer->getId();
                    }

                    $createBattery = $this->batteryService->extractCsvAndCreateBatteries($file, $manufacturerId, $user->getId());

                    if (!empty($createBattery) && !empty($createBattery['error'])) {
                        $this->addFlash('error', $this->translator->trans($createBattery['message']));
                    } else {
                        $this->addFlash('success', $this->translator->trans('service.success.battery_added_successfully'));

                        if (isset($createBattery['total']) && isset($createBattery['failure'])
                        && $createBattery['failure'] !== 0) {
                            $this->addFlash(
                                'warning',
                                $this->translator->trans(
                                    'service.success.battery_import_status',
                                    [
                                        '%failure_batteries%' => $createBattery['failure'],
                                        '%total_batteries%' => $createBattery['total']
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
            'bulk_import_batteries.html.twig',
            array(
                'form' => $form->createView(),
                'battery_types' => $this->batteryTypeService->getAvailableBatteryTypes()
            )
        );
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function detailAction(Request $request)
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $form = $this->createForm(BatteryDetailFormType::class, null, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $serialNumber = $formData['battery'] ?? null;

            if (empty($serialNumber)) {
                $this->addFlash('sonata_flash_error', 'Kindly Insert Valid Battery Serial Number!');
                return new RedirectResponse($this->admin->generateUrl('list'));
            }

            /** @var Battery|null $battery */
            $battery = $this->batteryService->fetchBatteryBySerialNumber($serialNumber, $user->getManufacturer() ?? null);

            if (empty($battery)) {
                $this->addFlash('sonata_flash_error', 'Battery does not exist!');
                return new RedirectResponse($this->admin->generateUrl('list'));
            }

            return $this->render(
                'battery/detail_view.html.twig', [
                    'battery' => $battery,
                    'path' => $this->admin->generateUrl('detail'),
                    'downloadPath' => $this->admin->generateUrl('download', [
                        'serialNumber' => $battery->getSerialNumber()
                    ])
                ]
            );
        }

        return $this->render(
            'battery/detail_form_view.html.twig',
            array(
                'form' => $form->createView(),
            )
        );
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function downloadAction(Request $request): RedirectResponse
    {
        $user = $this->security->getUser();
        $serialNumber = $request->get('serialNumber');

        if (empty($serialNumber)) {
            $this->addFlash('sonata_flash_error', 'Kindly Insert Valid Battery Serial Number!');
            return new RedirectResponse($this->admin->generateUrl('list'));
        }

        /** @var Battery|null $battery */
        $battery = $this->batteryService->fetchBatteryBySerialNumber($serialNumber, $user->getManufacturer() ?? null);

        if (empty($battery)) {
            $this->addFlash('sonata_flash_error', 'Battery does not exist!');
            return new RedirectResponse($this->admin->generateUrl('list'));
        }

        $this->pdfService->createBatteryPdf($battery);
        exit();
    }
}