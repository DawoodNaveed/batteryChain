<?php

namespace App\Controller;

use App\Entity\Battery;
use App\Entity\Manufacturer;
use App\Entity\User;
use App\Enum\BatteryEnum;
use App\Enum\RoleEnum;
use App\Form\BatteryDetailFormType;
use App\Form\BulkImportBatteryFormType;
use App\Form\GenerateLabelFormType;
use App\Helper\CustomHelper;
use App\Service\BatteryService;
use App\Service\BatteryTypeService;
use App\Service\CsvService;
use App\Service\ImportQueueService;
use App\Service\ImportService;
use App\Service\ManufacturerService;
use App\Service\PdfService;
use App\Service\ShipmentService;
use App\Service\TransactionLogService;
use App\Service\UserService;
use Doctrine\Inflector\InflectorFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sonata\AdminBundle\Controller\CRUDController;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Exception\BadRequestParamHttpException;
use Sonata\AdminBundle\Exception\LockException;
use Sonata\AdminBundle\Exception\ModelManagerException;
use Sonata\AdminBundle\Exception\ModelManagerThrowable;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
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
 * @property TransactionLogService transactionLogService
 * @property CsvService csvService
 * @property ShipmentService shipmentService
 * @property LoggerInterface logger
 * @property EntityManagerInterface entityManager
 * @property ImportService importService
 * @property ImportQueueService importQueueService
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
     * @param TransactionLogService $transactionLogService
     * @param CsvService $csvService
     * @param ShipmentService $shipmentService
     * @param LoggerInterface $logger
     * @param EntityManagerInterface $entityManager
     * @param ImportService $importService
     * @param ImportQueueService $importQueueService
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
        PdfService $pdfService,
        TransactionLogService $transactionLogService,
        CsvService $csvService,
        ShipmentService $shipmentService,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        ImportService $importService,
        ImportQueueService $importQueueService
    ) {
        $this->batteryService = $batteryService;
        $this->translator = $translator;
        $this->security = $security;
        $this->userService = $userService;
        $this->manufacturerService = $manufacturerService;
        $this->batteryTypeService = $batteryTypeService;
        $this->twig = $twig;
        $this->pdfService = $pdfService;
        $this->transactionLogService = $transactionLogService;
        $this->kernelProjectDir = $kernelProjectDir;
        $this->csvService = $csvService;
        $this->shipmentService = $shipmentService;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->importService = $importService;
        $this->importQueueService = $importQueueService;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function importAction(Request $request): Response
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $manufacturers = null;

        // In-Case of Super Admin
        if (in_array(RoleEnum::ROLE_SUPER_ADMIN, $this->getUser()->getRoles(), true) ||
            in_array(RoleEnum::ROLE_ADMIN, $this->getUser()->getRoles(), true)) {
            $manufacturers = $this->manufacturerService->getManufactures($user);
        }

        $form = $this->createForm(BulkImportBatteryFormType::class, null, [
            'manufacturer' => $manufacturers
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $formData->setManufacturer($formData->getManufacturer() ?? $user->getManufacturer());
            $manufacturer = $formData->getManufacturer();

            /** if already in process, notify */
            if (!empty($this->importService->findOneByFilter($manufacturer))) {
                $this->addFlash('sonata_flash_error',
                    $this->translator->trans("Import is already in process. Please wait for a while until it completes.")
                );

                return $this->redirectToRoute('admin_app_battery_import');
            }

            $formData->setCreated(new \DateTime('now'));
            $formData->setUpdated(new \DateTime('now'));
            $formData->setStatus('pending');
            $this->entityManager->persist($formData);
            $this->entityManager->flush();
            $this->addFlash('sonata_flash_success',
                $this->translator->trans("Csv is submitted for Bulk Import. You'll be notified soon.")
            );
            /** Send Message to Queue for Csv validation */
            $this->importQueueService->dispatchValidateBulkImportRequest([
                'data' => [
                    'import_id' => $formData->getId()
                ]
            ]);

            return $this->redirectToRoute('admin_app_import_list');
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

            /** @var Battery|null $manufacturerBattery */
            $manufacturerBattery = $user->getManufacturer() ? $this->batteryService->fetchBatteryBySerialNumber(
                $serialNumber,
                $user->getManufacturer() ?? null,
                $user->getManufacturer() ? false : true) : null;
            $otherManufacturerBatteries = $this->batteryService->fetchBatteriesBySerialNumber($serialNumber, !$user->getManufacturer());
            
            if ($manufacturerBattery && count($otherManufacturerBatteries) === BatteryEnum::MANUFACTURER_BATTERY_COUNT) {
                $deliveryCounter = 0;
                foreach ($manufacturerBattery->getTransactionLogs()->toArray() as $transaction) {
                    if ($transaction->getTransactionType() === CustomHelper::BATTERY_STATUS_DELIVERED) {
                        $deliveryCounter++;
                    }
                }
                return $this->render(
                    'battery/detail_view.html.twig', [
                        'battery' => $manufacturerBattery,
                        'path' => $this->admin->generateUrl('detail'),
                        'downloadPath' => $this->admin->generateUrl('download', [
                            'internalSerialNumber' => $manufacturerBattery->getInternalSerialNumber()
                        ]),
                        'detail' => isset(CustomHelper::BATTERY_STATUSES_DETAILS[$manufacturerBattery->getStatus()])
                            ? $this->translator->trans(CustomHelper::BATTERY_STATUSES_DETAILS[$manufacturerBattery->getStatus()])
                            : null,
                        'transactions' => $manufacturerBattery->getTransactionLogs()->toArray(),
                        'deliveryCounter' => $deliveryCounter
                    ]
                );
            }
            
            if ($otherManufacturerBatteries) {
                return $this->render(
                    'battery/battery_list_view.html.twig', [
                        'manufacturerBattery' => $manufacturerBattery,
                        'batteries' => $otherManufacturerBatteries
                    ]
                );
            }
    
            if (empty($manufacturerBattery)) {
                $this->addFlash('sonata_flash_error', 'Battery does not exist!');
                return new RedirectResponse($this->admin->generateUrl('list'));
            }
        }

        return $this->render(
            'battery/detail_form_view.html.twig',
            array(
                'form' => $form->createView(),
                'scan' => $this->admin->generateUrl('scanQr')
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
        $serialNumber = $request->get('internalSerialNumber');

        if (empty($serialNumber)) {
            $this->addFlash('sonata_flash_error', 'Kindly Insert Valid Battery Internal Serial Number!');
            return new RedirectResponse($this->admin->generateUrl('list'));
        }
    
        /** @var Battery|null $battery */
        $battery = $this->batteryService->batteryRepository->findOneBy(['internalSerialNumber' => $serialNumber]);

        if (empty($battery)) {
            $this->addFlash('sonata_flash_error', 'Battery does not exist!');
            return new RedirectResponse($this->admin->generateUrl('list'));
        }

        $this->pdfService->createBatteryPdf($battery);
        exit();
    }

    /**
     * @return Response
     */
    public function scanQrAction(): Response
    {
        return $this->render(
            'battery/scan.html.twig', [
                'path' => $this->admin->generateUrl('getScanResult'),
            ]
        );
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function getScanResultAction(Request $request): Response
    {
        /** @var Battery|null $battery */
        $battery = $this->batteryService
            ->fetchBatteryBySerialNumber($request->get('search'));

        if (empty($battery)) {
            $this->addFlash('danger', 'Kindly provide valid url query!');
            return new RedirectResponse($this->admin->generateUrl('detail'));
        }

        return $this->render(
            'battery/detail_view.html.twig', [
                'battery' => $battery,
                'path' => $this->admin->generateUrl('detail'),
                'downloadPath' => $this->admin->generateUrl('download', [
                    'internalSerialNumber' => $battery->getInternalSerialNumber()
                ]),
                'detail' => isset(CustomHelper::BATTERY_STATUSES_DETAILS[$battery->getStatus()])
                    ? $this->translator->trans(CustomHelper::BATTERY_STATUSES_DETAILS[$battery->getStatus()])
                    : null
            ]
        );
    }

    /**
     * @return Response
     */
    public function reportAction(): Response
    {
        return $this->render(
            'report/view.html.twig', [
                'download' => $this->admin->generateUrl('downloadReport'),
                'downloadAsPdf' => $this->admin->generateUrl('downloadReportAsPdf'),
                'manufacturers' => $this->manufacturerService->manufacturerRepository->findAll(),
                'types' => $this->batteryTypeService->batteryTypeRepository->findAll()
            ]
        );
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function getReportAction(Request $request): Response
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $manufacturer = null;
        // In-Case of Manufacturer
        if ((!in_array(RoleEnum::ROLE_SUPER_ADMIN, $this->getUser()->getRoles(), true) &&
                !in_array(RoleEnum::ROLE_ADMIN, $this->getUser()->getRoles(), true)) &&
            in_array(RoleEnum::ROLE_MANUFACTURER, $this->getUser()->getRoles(), true)
        ) {
            $manufacturer = $user->getManufacturer();
        }

        $filters = [];
        $formData = $request->get('formData');
        parse_str($formData, $filters);
        $data = null;
        $batteries = $this->batteryService->getBatteriesArrayByFilters($filters, $manufacturer);

        return new JsonResponse([
            'data' => $batteries,
            'status' => count($batteries) > 0
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function downloadReportAction(Request $request): Response
    {
        $filters = $request->query->all();

        if (empty($filters)) {
            return new RedirectResponse($this->admin->generateUrl('report'));
        }

        /** @var User $user */
        $user = $this->security->getUser();
        $manufacturer = null;
        $filename = 'Battery Report' . ' | ';

        // In-Case of Manufacturer
        if ((!in_array(RoleEnum::ROLE_SUPER_ADMIN, $this->getUser()->getRoles(), true) &&
            !in_array(RoleEnum::ROLE_ADMIN, $this->getUser()->getRoles(), true)) &&
            in_array(RoleEnum::ROLE_MANUFACTURER, $this->getUser()->getRoles(), true)
        ) {
            $filename .= $user->getManufacturer()->getName() . ' | ';
            $manufacturer = $user->getManufacturer();
        } else {
            $filename .= $filters['manufacturer'] . ' | ';
        }

        $filename .= ucwords($filters['mode']) . ' Batteries' . ' | ';
        $data = null;
        $batteries = $this->batteryService->getBatteriesByFilters($filters, $filename, $manufacturer);
        $this->csvService->arrayToCSVDownload($batteries, $filename . '.csv');
        exit();
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function downloadReportAsPdfAction(Request $request): Response
    {
        $filters = $request->query->all();

        if (empty($filters)) {
            return new RedirectResponse($this->admin->generateUrl('report'));
        }

        /** @var User $user */
        $user = $this->security->getUser();
        $manufacturer = null;
        $filename = 'Battery Report' . ' | ';

        // In-Case of Manufacturer
        if ((!in_array(RoleEnum::ROLE_SUPER_ADMIN, $this->getUser()->getRoles(), true) &&
            !in_array(RoleEnum::ROLE_ADMIN, $this->getUser()->getRoles(), true)) &&
            in_array(RoleEnum::ROLE_MANUFACTURER, $this->getUser()->getRoles(), true)
        ) {
            $filename .= $user->getManufacturer()->getName() . ' | ';
            $manufacturer = $user->getManufacturer();
        } else {
            $filename .= $filters['manufacturer'] . ' | ';
        }

        $filename .= ucwords($filters['mode']) . ' Batteries' . ' | ';
        $batteries = $this->batteryService->getBatteriesByFilters($filters, $filename, $manufacturer);
        $this->pdfService->createBatteriesReportPdf($batteries, $filename);
        exit();
    }

    /**
     * @param ProxyQueryInterface $selectedModelQuery
     * @param Request $request
     * @return RedirectResponse
     */
    public function batchActionRegister(ProxyQueryInterface $selectedModelQuery, Request $request): RedirectResponse
    {
        $this->admin->checkAccess('edit');
        $this->admin->checkAccess('delete');
        $selectedModels = $selectedModelQuery->execute();

        try {
            foreach ($selectedModels as $selectedModel) {
                $ids[] = $selectedModel->getId();
            }

            return new RedirectResponse(
                $this->admin->generateUrl('register', [
                    'ids' => $ids,
                ])
            );
        } catch (\Exception $e) {
            $this->addFlash('sonata_flash_error', $e->getMessage());

            return new RedirectResponse(
                $this->admin->generateUrl('list', [
                    'filter' => $this->admin->getFilterParameters()
                ])
            );
        }
    }

    /**
     * @param ProxyQueryInterface $selectedModelQuery
     * @param Request $request
     * @return RedirectResponse
     */
    public function batchActionDeliver(ProxyQueryInterface $selectedModelQuery, Request $request): RedirectResponse
    {
        $this->admin->checkAccess('edit');
        $this->admin->checkAccess('delete');
        $selectedModels = $selectedModelQuery->execute();
        $failure = 0;
        $ids = null;
        $importId = null;
        $fromIntermediate = false;
        /** If request came from intermediate screen */
        if (strpos($_SERVER['REQUEST_URI'], "battery-intermediate") !== false){
            $fromIntermediate = true;
        }

        try {
            foreach ($selectedModels as $selectedModel) {
                $ids[] = $selectedModel->getId();
                $battery = $this->batteryService->batteryRepository->find($selectedModel->getId());

                if (!empty($battery)
                    && $battery->getStatus() === CustomHelper::BATTERY_STATUS_PRE_REGISTERED
                    && !($this->transactionLogService->isExist($battery, CustomHelper::BATTERY_STATUS_REGISTERED))) {

                    /** Once register/deliver from intermediate screen, we'll hide it  */
                    if (empty($importId) && $fromIntermediate) {
                        $importObj = $battery->getImport() ?? null;
                        /** updated implementation */
                        if ($importObj) {
                            $this->batteryService->updateBulkImportField($importObj);
                            $importObj->setDeletedAt(new \DateTime('now'));
                        }
                    }

                    $battery->setStatus(CustomHelper::BATTERY_STATUS_DELIVERED);
                    $battery->setIsBulkImport(false);
                    $battery->setUpdated(new \DateTime('now'));
                    $this->transactionLogService->createTransactionLog(
                        $battery,
                        CustomHelper::BATTERY_STATUS_REGISTERED
                    );
                    /** @var Battery $battery */
                    $transactionLog = $this->transactionLogService->createDeliveryTransactionLog(
                        $battery,
                        $battery->getManufacturer()->getUser(),
                        null,
                        CustomHelper::BATTERY_STATUS_DELIVERED,
                        CustomHelper::STATUS_PENDING,
                        $battery->getDeliveryDate()
                    );
                    $this->shipmentService
                        ->createShipment(
                            $battery->getManufacturer()->getUser(),
                            $battery,
                            $transactionLog,
                            $battery->getDeliveryDate()
                        );
                } else {
                    $failure++;
                }

            }

            if ($failure === count($ids)) {
                $this->addFlash(
                    'sonata_flash_error',
                    $this->translator->trans('Failure! All Selected Battery(s) are already registered/delivered or in queue to be registered/delivered soon!')
                );
            } else if ($failure > 0) {
                $this->addFlash(
                    'sonata_flash_info',
                    $this->translator->trans('service.error.bulk_deliver_batteries', [
                        '%failure_batteries%' => $failure
                    ])
                );
            }

            if ($failure !== count($ids)) {
                $this->addFlash(
                    'sonata_flash_success',
                    $this->translator->trans('Success! Request to Deliver Battery is submitted! %count% Battery(s) will be delivered soon!', [
                        '%count%' => count($ids) - $failure
                    ])
                );
            }

            return $this->redirectToRoute('admin_app_battery_list');
        } catch (\Exception $e) {
            $this->addFlash('sonata_flash_error', $e->getMessage());

            return new RedirectResponse(
                $this->admin->generateUrl('list', [
                    'filter' => $this->admin->getFilterParameters()
                ])
            );
        }
    }

    /**
     * @param ProxyQueryInterface $selectedModelQuery
     * @param Request $request
     * @return RedirectResponse
     */
    public function batchActionDownload(ProxyQueryInterface $selectedModelQuery, Request $request): RedirectResponse
    {
        $this->admin->checkAccess('edit');
        $this->admin->checkAccess('delete');
        $selectedModels = $selectedModelQuery->execute();

        try {
            foreach ($selectedModels as $selectedModel) {
                $ids[] = $selectedModel->getId();
            }

            return new RedirectResponse(
                $this->admin->generateUrl('downloadBatchPdf', [
                    'ids' => $ids,
                ])
            );
        } catch (\Exception $e) {
            $this->addFlash('sonata_flash_error', $e->getMessage());

            return new RedirectResponse(
                $this->admin->generateUrl('list', [
                    'filter' => $this->admin->getFilterParameters()
                ])
            );
        }
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function downloadBatchPdfAction(Request $request): RedirectResponse
    {
        $batteries = $this->batteryService->getBatteriesByIds($request->get('ids'));
        $this->pdfService->createBatteriesReportPdf($batteries, 'Batteries');
        exit();
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function registerAction(Request $request): RedirectResponse
    {
        try {
            $importId = null;
            $fromIntermediate = false;
            /** If request came from intermediate screen */
            if (strpos($_SERVER['REQUEST_URI'], "battery-intermediate") !== false){
                $fromIntermediate = true;
            }

            $manufacturer = null;
            $ids = $request->get('ids');
            $failure = 0;

            if ($ids === null) {
                $this->addFlash(
                    'sonata_flash_error',
                    $this->translator->trans('Action aborted. No items were selected.')
                );

                return new RedirectResponse($this->admin->generateUrl('list'));
            }

            foreach ($ids as $id) {
                $battery = $this->batteryService->batteryRepository->find($id);

                if (!empty($battery)
                    && $battery->getStatus() === CustomHelper::BATTERY_STATUS_PRE_REGISTERED
                    && !($this->transactionLogService->isExist($battery, CustomHelper::BATTERY_STATUS_REGISTERED))) {

                    if (empty($importId) && $fromIntermediate) {
                        $importObj = $battery->getImport() ?? null;

                        /** updated implementation */
                        if ($importObj) {
                            $this->batteryService->updateBulkImportField($importObj);
                            $importObj->setDeletedAt(new \DateTime('now'));
                        }
                    }

                    $battery->setStatus(CustomHelper::BATTERY_STATUS_REGISTERED);
                    $battery->setIsBulkImport(false);
                    $battery->setUpdated(new \DateTime('now'));
                    $this->transactionLogService->createTransactionLog(
                        $battery,
                        CustomHelper::BATTERY_STATUS_REGISTERED
                    );
                } else {
                    $failure++;
                }
            }

            if ($failure === count($ids)) {
                $this->addFlash(
                    'sonata_flash_error',
                    $this->translator->trans('Failure! All Selected Battery(s) are already registered or in queue to be registered soon!')
                );
            } else if ($failure > 0) {
                $this->addFlash(
                    'sonata_flash_info',
                    $this->translator->trans('service.error.bulk_register_batteries', [
                        '%failure_batteries%' => $failure
                    ])
                );
            }

            if ($failure !== count($ids)) {
                $this->addFlash(
                    'sonata_flash_success',
                    $this->translator->trans('Success! Request to Register Battery is submitted! %count% Battery(s) will be registered soon!', [
                        '%count%' => count($ids) - $failure
                    ])
                );
            }

            return new RedirectResponse($this->admin->generateUrl('list', [
                'filter' => $this->admin->getFilterParameters()
            ]));
        } catch (\Exception $exception) {
            $this->addFlash('sonata_flash_error', $exception->getMessage());

            return new RedirectResponse(
                $this->admin->generateUrl('list', [
                    'filter' => $this->admin->getFilterParameters()
                ])
            );
        }
    }

    /**
     * @param Request $request
     * @param object $object
     * @return Response|null
     */
    protected function preDelete(Request $request, object $object): ?Response
    {
        if ($object->getStatus() != CustomHelper::BATTERY_STATUS_PRE_REGISTERED) {
            $request->getSession()
                ->getFlashBag()
                ->add('sonata_flash_error','You can only delete Pre-registered Battery!');
            return $this->redirectToList();
        }

        return parent::preDelete($request, $object); // TODO: Change the autogenerated stub
    }

    /**
     * @throws NotFoundHttpException If the HTTP method is not POST
     * @throws \RuntimeException     If the batch action is not defined
     */
    public function batchAction(Request $request): Response
    {
        $restMethod = $request->getMethod();

        $confirmation = $request->get('confirmation', false);

        $forwardedRequest = $request->duplicate();

        $encodedData = $request->get('data');

        if (null === $encodedData) {
            $action = $forwardedRequest->request->get('action');
            /** @var InputBag|ParameterBag $bag */
            $bag = $request->request;
            if ($bag instanceof InputBag) {
                // symfony 5.1+
                $idx = $bag->all('idx');
            } else {
                $idx = (array) $bag->get('idx', []);
            }
            $allElements = $forwardedRequest->request->getBoolean('all_elements');

            $forwardedRequest->request->set('idx', $idx);
            $forwardedRequest->request->set('all_elements', (string) $allElements);

            $data = $forwardedRequest->request->all();
            $data['all_elements'] = $allElements;

            unset($data['_sonata_csrf_token']);
        } else {
            if (!\is_string($encodedData)) {
                throw new BadRequestParamHttpException('data', 'string', $encodedData);
            }

            try {
                $data = json_decode($encodedData, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw new BadRequestHttpException('Unable to decode batch data');
            }

            $action = $data['action'];
            $idx = (array) ($data['idx'] ?? []);
            $allElements = (bool) ($data['all_elements'] ?? false);
            $forwardedRequest->request->replace(array_merge($forwardedRequest->request->all(), $data));
        }

        if (!\is_string($action)) {
            throw new \RuntimeException('The action is not defined');
        }

        $batchActions = $this->admin->getBatchActions();
        if (!\array_key_exists($action, $batchActions)) {
            throw new \RuntimeException(sprintf('The `%s` batch action is not defined', $action));
        }

        $camelizedAction = InflectorFactory::create()->build()->classify($action);
        $isRelevantAction = sprintf('batchAction%sIsRelevant', $camelizedAction);

        if (method_exists($this, $isRelevantAction)) {
            $nonRelevantMessage = $this->$isRelevantAction($idx, $allElements, $forwardedRequest);
        } else {
            $nonRelevantMessage = 0 !== \count($idx) || $allElements; // at least one item is selected
        }

        if (!$nonRelevantMessage) { // default non relevant message (if false of null)
            $nonRelevantMessage = 'flash_batch_empty';
        }

        $datagrid = $this->admin->getDatagrid();
        $datagrid->buildPager();

        if (true !== $nonRelevantMessage) {
            $this->addFlash(
                'sonata_flash_info',
                $this->trans($nonRelevantMessage, [], 'SonataAdminBundle')
            );

            return $this->redirectToList();
        }

        $askConfirmation = $batchActions[$action]['ask_confirmation'] ?? true;

        if (true === $askConfirmation && 'ok' !== $confirmation) {
            $actionLabel = $batchActions[$action]['label'];
            $batchTranslationDomain = $batchActions[$action]['translation_domain'] ??
                $this->admin->getTranslationDomain();

            $formView = $datagrid->getForm()->createView();
            $this->setFormTheme($formView, $this->admin->getFilterTheme());

            $template = $batchActions[$action]['template']
                ?? $this->admin->getTemplateRegistry()->getTemplate('batch_confirmation');

            return $this->renderWithExtraParams($template, [
                'action' => 'list',
                'action_label' => $actionLabel,
                'batch_translation_domain' => $batchTranslationDomain,
                'datagrid' => $datagrid,
                'form' => $formView,
                'data' => $data,
                'csrf_token' => $this->getCsrfToken('sonata.batch'),
            ]);
        }

        // execute the action, batchActionXxxxx
        $finalAction = sprintf('batchAction%s', $camelizedAction);
        if (!method_exists($this, $finalAction)) {
            throw new \RuntimeException(sprintf('A `%s::%s` method must be callable', static::class, $finalAction));
        }

        $query = $datagrid->getQuery();

        $query->setFirstResult(null);
        $query->setMaxResults(null);

        $this->admin->preBatchAction($action, $query, $idx, $allElements);

        foreach ($this->admin->getExtensions() as $extension) {
            // NEXT_MAJOR: Remove the if-statement around the call to `$extension->preBatchAction()`
            // @phpstan-ignore-next-line
            if (method_exists($extension, 'preBatchAction')) {
                $extension->preBatchAction($this->admin, $action, $query, $idx, $allElements);
            }
        }

        if (\count($idx) > 0) {
            $this->admin->getModelManager()->addIdentifiersToQuery($this->admin->getClass(), $query, $idx);
        } elseif (!$allElements) {
            $this->addFlash(
                'sonata_flash_info',
                $this->trans('flash_batch_no_elements_processed', [], 'SonataAdminBundle')
            );

            return $this->redirectToList();
        }

        return $this->$finalAction($query, $forwardedRequest);
    }

    /**
     * @param Request $request
     * @param object $object
     * @return Response|null
     */
    protected function preEdit(Request $request, object $object): ?Response
    {
        if ($object->getStatus() !== CustomHelper::BATTERY_STATUS_PRE_REGISTERED) {
            $this->addFlash(
                'sonata_flash_info',
                $this->translator->trans('You cannot edit any information for a battery which is already registered!')
            );
        }

        return parent::preEdit($request, $object); // TODO: Change the autogenerated stub
    }

    /**
     * @param ProxyQueryInterface $query
     * @return Response
     * @throws ModelManagerThrowable
     */
    public function batchActionDelete(ProxyQueryInterface $query): Response
    {
        $this->admin->checkAccess('batchDelete');
        $modelManager = $this->admin->getModelManager();
        $selectedModels = $query->execute();
        $failure = 0;

        foreach ($selectedModels as $selectedModel) {
            if ($selectedModel->getStatus() !== 'pre-registered') {
                $failure++;
            }
        }

        if (count($selectedModels) === $failure) {
            $this->addFlash(
                'sonata_flash_error',
                $this->trans('You can only delete pre-registered battery(s).', [], 'SonataAdminBundle')
            );
            return $this->redirectToList();
        }

        $query->andWhere('o.status = :status')
            ->setParameter('status', 'pre-registered');

        try {
            $modelManager->batchDelete($this->admin->getClass(), $query);

            if ($failure > 0) {
                $this->addFlash(
                    'sonata_flash_error',
                    $this->trans('Alert! %count% selected items are already registered so you cannot delete them.', [
                        '%count%' => $failure
                    ], 'messages')
                );
            }

            $this->addFlash(
                'sonata_flash_success',
                $this->trans('Success! Only %count% pre-registered selected battery(s) have been successfully deleted.', [
                    '%count%' => count($selectedModels) - $failure
                ], 'messages')
            );
        } catch (ModelManagerException $e) {
            // NEXT_MAJOR: Remove this catch.
            $this->handleModelManagerException($e);

            $this->addFlash(
                'sonata_flash_error',
                $this->trans('flash_batch_delete_error', [], 'SonataAdminBundle')
            );
        } catch (ModelManagerThrowable $e) {
            $errorMessage = $this->handleModelManagerThrowable($e);

            $this->addFlash(
                'sonata_flash_error',
                $errorMessage ?? $this->trans('flash_batch_delete_error', [], 'SonataAdminBundle')
            );
        }

        return $this->redirectToList();
    }

    /**
     * @param Request $request
     * @return Response
     * @throws ModelManagerThrowable
     * @throws \ReflectionException
     */
    public function createAction(Request $request): Response
    {
        $this->assertObjectExists($request);

        $this->admin->checkAccess('create');

        // the key used to lookup the template
        $templateKey = 'edit';

        $class = new \ReflectionClass($this->admin->hasActiveSubClass() ? $this->admin->getActiveSubClass() : $this->admin->getClass());

        if ($class->isAbstract()) {
            return $this->renderWithExtraParams(
                '@SonataAdmin/CRUD/select_subclass.html.twig',
                [
                    'action' => 'create',
                ]
            );
        }

        $newObject = $this->admin->getNewInstance();

        $preResponse = $this->preCreate($request, $newObject);
        if (null !== $preResponse) {
            return $preResponse;
        }

        $this->admin->setSubject($newObject);

        $form = $this->admin->getForm();

        $form->setData($newObject);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $isFormValid = $form->isValid();

            // persist if the form was valid and if in preview mode the preview was approved
            if ($isFormValid && (!$this->isInPreviewMode($request) || $this->isPreviewApproved($request))) {
                /** @phpstan-var T $submittedObject */
                $submittedObject = $form->getData();
                $this->admin->setSubject($submittedObject);
                $this->admin->checkAccess('create', $submittedObject);
                try {
                    /** @var User $user */
                    $user = $this->getUser();
                    $manufacturer = $submittedObject->getManufacturer() ?? $user->getManufacturer();
                    // Battery with similar serial number
                    $battery = $this->batteryService->batteryRepository->findOneBy([
                        'serialNumber' => $submittedObject->getSerialNumber(),
                        'manufacturer' => $manufacturer
                    ]);

                    // If battery exists
                    if (!empty($battery)) {
                        $this->addFlash(
                            'sonata_flash_error',
                            $this->trans(
                                'flash_create_error_exist',
                                ['%name%' => $this->escapeHtml($this->admin->toString($newObject))],
                                'messages'
                            )
                        );
                    } else {
                        $submittedObject->setInternalSerialNumber(
                            $manufacturer->getIdentifier() . '-' . $submittedObject->getSerialNumber()
                        );
                        $newObject = $this->admin->create($submittedObject);

                        if ($this->isXmlHttpRequest($request)) {
                            return $this->handleXmlHttpRequestSuccessResponse($request, $newObject);
                        }

                        $this->addFlash(
                            'sonata_flash_success',
                            $this->trans(
                                'flash_create_success',
                                ['%name%' => $this->escapeHtml($this->admin->toString($newObject))],
                                'SonataAdminBundle'
                            )
                        );

                        // redirect to edit mode
                        return $this->redirectTo($request, $newObject);
                    }
                } catch (ModelManagerException $e) {
                    // NEXT_MAJOR: Remove this catch.
                    $this->handleModelManagerException($e);

                    $isFormValid = false;
                } catch (ModelManagerThrowable $e) {
                    $errorMessage = $this->handleModelManagerThrowable($e);

                    $isFormValid = false;
                }

            }

            // show an error message if the form failed validation
            if (!$isFormValid) {
                if ($this->isXmlHttpRequest($request) && null !== ($response = $this->handleXmlHttpRequestErrorResponse($request, $form))) {
                    return $response;
                }

                $this->addFlash(
                    'sonata_flash_error',
                    $errorMessage ?? $this->trans(
                        'flash_create_error',
                        ['%name%' => $this->escapeHtml($this->admin->toString($newObject))],
                        'SonataAdminBundle'
                    )
                );
            } elseif ($this->isPreviewRequested($request)) {
                // pick the preview template if the form was valid and preview was requested
                $templateKey = 'preview';
                $this->admin->getShow();
            }
        }

        $formView = $form->createView();
        // set the theme for the current Admin Form
        $this->setFormTheme($formView, $this->admin->getFormTheme());

        $template = $this->admin->getTemplateRegistry()->getTemplate($templateKey);

        return $this->renderWithExtraParams($template, [
            'action' => 'create',
            'form' => $formView,
            'object' => $newObject,
            'objectId' => null,
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws ModelManagerThrowable
     */
    public function editAction(Request $request): Response
    {
        // the key used to lookup the template
        $templateKey = 'edit';

        $existingObject = $this->assertObjectExists($request, true);
        \assert(null !== $existingObject);

        $this->checkParentChildAssociation($request, $existingObject);

        $this->admin->checkAccess('edit', $existingObject);

        $preResponse = $this->preEdit($request, $existingObject);
        if (null !== $preResponse) {
            return $preResponse;
        }

        $this->admin->setSubject($existingObject);
        $objectId = $this->admin->getNormalizedIdentifier($existingObject);
        \assert(null !== $objectId);

        $form = $this->admin->getForm();

        $form->setData($existingObject);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $isFormValid = $form->isValid();

            // persist if the form was valid and if in preview mode the preview was approved
            if ($isFormValid && (!$this->isInPreviewMode($request) || $this->isPreviewApproved($request))) {
                /** @phpstan-var T $submittedObject */
                $submittedObject = $form->getData();
                $this->admin->setSubject($submittedObject);

                try {
                    /** @var User $user */
                    $user = $this->getUser();
                    $manufacturer = $submittedObject->getManufacturer() ?? $user->getManufacturer();
                    // If any manufacturer has battery with similar serial number
                    $battery = $this->batteryService->batteryRepository->findOneBy([
                        'serialNumber' => $submittedObject->getSerialNumber(),
                        'manufacturer' => $manufacturer
                    ]);

                    // Battery exist and Skip itself
                    if (!empty($battery) && $battery->getId() !== $submittedObject->getId()) {
                        $this->addFlash(
                            'sonata_flash_error',
                            $this->trans(
                                'flash_edit_error_exist',
                                ['%name%' => $this->escapeHtml($this->admin->toString($submittedObject))],
                                'messages'
                            )
                        );
                    } else {
                        $submittedObject->setInternalSerialNumber(
                            $manufacturer->getIdentifier() . '-' . $submittedObject->getSerialNumber()
                        );
                        $existingObject = $this->admin->update($submittedObject);

                        if ($this->isXmlHttpRequest($request)) {
                            return $this->handleXmlHttpRequestSuccessResponse($request, $existingObject);
                        }

                        $this->addFlash(
                            'sonata_flash_success',
                            $this->trans(
                                'flash_edit_success',
                                ['%name%' => $this->escapeHtml($this->admin->toString($existingObject))],
                                'SonataAdminBundle'
                            )
                        );

                        // redirect to edit mode
                        return $this->redirectTo($request, $existingObject);
                    }
                } catch (ModelManagerException $e) {
                    // NEXT_MAJOR: Remove this catch.
                    $this->handleModelManagerException($e);

                    $isFormValid = false;
                } catch (ModelManagerThrowable $e) {
                    $errorMessage = $this->handleModelManagerThrowable($e);

                    $isFormValid = false;
                } catch (LockException $e) {
                    $this->addFlash('sonata_flash_error', $this->trans('flash_lock_error', [
                        '%name%' => $this->escapeHtml($this->admin->toString($existingObject)),
                        '%link_start%' => sprintf('<a href="%s">', $this->admin->generateObjectUrl('edit', $existingObject)),
                        '%link_end%' => '</a>',
                    ], 'SonataAdminBundle'));
                }
            }

            // show an error message if the form failed validation
            if (!$isFormValid) {
                if ($this->isXmlHttpRequest($request) && null !== ($response = $this->handleXmlHttpRequestErrorResponse($request, $form))) {
                    return $response;
                }

                $this->addFlash(
                    'sonata_flash_error',
                    $errorMessage ?? $this->trans(
                        'flash_edit_error',
                        ['%name%' => $this->escapeHtml($this->admin->toString($existingObject))],
                        'SonataAdminBundle'
                    )
                );
            } elseif ($this->isPreviewRequested($request)) {
                // enable the preview template if the form was valid and preview was requested
                $templateKey = 'preview';
                $this->admin->getShow();
            }
        }

        $formView = $form->createView();
        // set the theme for the current Admin Form
        $this->setFormTheme($formView, $this->admin->getFormTheme());

        $template = $this->admin->getTemplateRegistry()->getTemplate($templateKey);

        return $this->renderWithExtraParams($template, [
            'action' => 'edit',
            'form' => $formView,
            'object' => $existingObject,
            'objectId' => $objectId,
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function labelAction(Request $request): Response
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $manufacturers = $this->manufacturerService->manufacturerRepository->findAll();
        $isAdmin = true;

        if (!in_array(RoleEnum::ROLE_SUPER_ADMIN, $user->getRoles(), true) &&
            !in_array(RoleEnum::ROLE_ADMIN, $user->getRoles(), true) &&
            in_array(RoleEnum::ROLE_MANUFACTURER, $user->getRoles(), true) ) {
            $isAdmin = false;
        }

        $form = $this->createForm(GenerateLabelFormType::class, null, [
            'manufacturer' => $this->manufacturerService->toChoiceArray($manufacturers),
            'is_admin' => $isAdmin
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            /** @var Manufacturer $batteryManufacturer */
            $batteryManufacturer = $formData['manufacturer'] ?? null;
            $serialNumber = $formData['battery'] ?? null;

            if (empty($serialNumber)) {
                $this->addFlash('sonata_flash_error', 'Kindly Insert Valid Battery Serial Number!');
                return new RedirectResponse($this->admin->generateUrl('label'));
            }

            /** @var Battery|null $battery */
            $battery = $this->batteryService->fetchBatteryBySerialNumber(
                $serialNumber,
                $batteryManufacturer ?: $user->getManufacturer(),
                !$user->getManufacturer()
            );

            if (empty($battery) || $battery->getStatus() === CustomHelper::BATTERY_STATUS_PRE_REGISTERED) {
                $this->addFlash('sonata_flash_error', 'Battery may not exist or registered!');
                return new RedirectResponse($this->admin->generateUrl('label'));
            }

            return $this->render(
                'battery/label.html.twig', [
                    'battery' => $battery,
                    'path' => $this->admin->generateUrl('label'),
                    'downloadPath' => $this->admin->generateUrl('downloadLabel', [
                        'internalSerialNumber' => $battery->getInternalSerialNumber()
                    ])
                ]
            );
        }

        return $this->render(
            'battery/generateLabel.html.twig',
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
    public function downloadLabelAction(Request $request): RedirectResponse
    {
        $serialNumber = $request->get('internalSerialNumber');

        if (empty($serialNumber)) {
            $this->addFlash('sonata_flash_error', 'Kindly Insert Valid Battery Serial Number!');
            return new RedirectResponse($this->admin->generateUrl('list'));
        }

        /** @var Battery|null $battery */
        $battery = $this->batteryService->batteryRepository->findOneBy(['internalSerialNumber' => $serialNumber]);

        $this->pdfService->createBatteryLabelPdf($battery);
        exit();
    }
    
    /**
     * @param Request $request
     * @return Response
     */
    public function getBatteryDetailsByIdAction(Request $request): Response
    {
        $battery = $this->batteryService->find((int)$request->get('id'));
    
        $deliveryCounter = 0;
        foreach ($battery->getTransactionLogs()->toArray() as $transaction) {
            if ($transaction->getTransactionType() === CustomHelper::BATTERY_STATUS_DELIVERED) {
                $deliveryCounter++;
            }
        }
    
        return $this->render(
            'battery/detail_view.html.twig', [
                'battery' => $battery,
                'path' => $this->admin->generateUrl('detail'),
                'downloadPath' => $this->admin->generateUrl('download', [
                    'internalSerialNumber' => $battery->getInternalSerialNumber()
                ]),
                'detail' => isset(CustomHelper::BATTERY_STATUSES_DETAILS[$battery->getStatus()])
                    ? $this->translator->trans(CustomHelper::BATTERY_STATUSES_DETAILS[$battery->getStatus()])
                    : null,
                'transactions' => $battery->getTransactionLogs()->toArray(),
                'deliveryCounter' => $deliveryCounter
            ]
        );
    }
}