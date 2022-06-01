<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\BulkImportBatteryFormType;
use App\Service\BatteryService;
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

/**
 * Class BatteryController
 * @package App\Controller
 * @property BatteryService batteryService
 * @property TranslatorInterface translator
 * @property Security security
 * @property UserService userService
 */
class BatteryController extends CRUDController
{
    public function __construct(
        BatteryService $batteryService,
        TranslatorInterface $translator,
        Security $security,
        UserService $userService
    ) {
        $this->batteryService = $batteryService;
        $this->translator = $translator;
        $this->security = $security;
        $this->userService = $userService;
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception|DBALException
     */
    public function importAction(Request $request): Response
    {
        $form = $this->createForm(BulkImportBatteryFormType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $file = $request->files->all();

            if (!empty($file) && isset($file['bulk_import_battery_form']['csv'])) {
                /** @var UploadedFile $file */
                $file = $file['bulk_import_battery_form']['csv'];
                $validCsv = $this->batteryService->isValidCsv($file);
                if ($validCsv['error'] == 0) {
                    /** @var User $user */
                    $user = $this->security->getUser();
                    $manufacturerId = $this->userService->getManufacturerId($user);
                    $createClone = $this->batteryService->extractCsvAndCreateBatteries($file, $manufacturerId, $user->getId());

                    if (!empty($createClone) && $createClone['error']) {
                        $this->addFlash('error', $this->translator->trans($createClone['message']));
                    } else {
                        $this->addFlash('success', $this->translator->trans('service.success.battery_added_successfully'));
                        return new RedirectResponse($this->admin->generateUrl('list'));
                    }
                } else {
                    $this->addFlash('error', $this->translator->trans($validCsv['message']));
                }
            }
        }

        return $this->render(
            'bulk_import_batteries.html.twig',
            array(
                'form' => $form->createView(),
            )
        );
    }
}