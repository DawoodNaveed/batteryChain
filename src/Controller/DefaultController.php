<?php

namespace App\Controller;

use App\Entity\Battery;
use App\Form\BatteryDetailFormType;
use App\Helper\CustomHelper;
use App\Service\BatteryService;
use App\Service\EncryptionService;
use App\Service\PdfService;
use App\Service\ReCaptchaService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class DefaultController
 * @package App\Controller
 * @property UserService userService
 * @property UrlGeneratorInterface urlGenerator
 * @property BatteryService batteryService
 * @property ReCaptchaService reCaptchaService
 * @property Environment twig
 * @property PdfService pdfService
 * @property EncryptionService encryptionService
 */
class DefaultController extends AbstractController
{
    /**
     * DefaultController constructor.
     * @param UserService $userService
     * @param UrlGeneratorInterface $urlGenerator
     * @param BatteryService $batteryService
     * @param ReCaptchaService $reCaptchaService
     * @param Environment $twig
     * @param PdfService $pdfService
     * @param EncryptionService $encryptionService
     */
    public function __construct(
        UserService $userService,
        UrlGeneratorInterface $urlGenerator,
        BatteryService $batteryService,
        ReCaptchaService $reCaptchaService,
        Environment $twig,
        PdfService $pdfService,
        EncryptionService $encryptionService
    ) {
        $this->userService = $userService;
        $this->urlGenerator = $urlGenerator;
        $this->batteryService = $batteryService;
        $this->reCaptchaService = $reCaptchaService;
        $this->twig = $twig;
        $this->pdfService = $pdfService;
        $this->encryptionService = $encryptionService;
    }
    /**
     * @param Request $request
     * @return Response
     * @Route(name="homepage")
     */
    public function index(Request $request): Response
    {
        /** If Logged In - redirect to Dashboard */
        if ($this->userService->isAuthenticated()) {
            return new RedirectResponse($this->urlGenerator->generate('home'));
        }

        $form = $this->createForm(BatteryDetailFormType::class, null, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!($this->reCaptchaService->validateReCaptcha($request))) {
                $this->addFlash('danger', "The reCAPTCHA wasn't entered correctly.");
                return new RedirectResponse('/');
            }

            $formData = $form->getData();
            $serialNumber = $formData['battery'] ?? null;

            if (empty($serialNumber)) {
                $this->addFlash('danger', 'Kindly Insert Valid Battery Serial Number!');
                return new RedirectResponse('/');
            }

            return new RedirectResponse($this->generateUrl('battery_detail', [
                'search' => $this->encryptionService->encryptString($serialNumber)
            ]));
        }

        return $this->render(
            'public_templates/detail_form_view.html.twig',
            array(
                'searchForm' => $form->createView(),
                'public' => true
            )
        );
    }

    /**
     * @param Request $request
     * @param $slug
     * @return RedirectResponse
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @Route(name="download", path="/download/{slug}")
     */
    public function downloadAction(Request $request, $slug): RedirectResponse
    {
        if (empty($slug)) {
            $this->addFlash('danger', 'Kindly Insert Valid Battery Serial Number!');
            return new RedirectResponse('/');
        }

        $serialNumber = $this->encryptionService
            ->validateAndFetchSerialNumber(
                $this->encryptionService->decryptString($slug)
            );

        if ($serialNumber === false) {
            $this->addFlash('danger', 'Kindly provide valid url query!');
            return new RedirectResponse($this->generateUrl('homepage'));
        }

        /** @var Battery|null $battery */
        $battery = $this->batteryService->fetchBatteryBySerialNumber($serialNumber);

        if (empty($battery)) {
            $this->addFlash('danger', 'Battery does not exist!');
            return new RedirectResponse('/');
        }

        $this->pdfService->createBatteryPdf($battery);
        exit();
    }

    /**
     * @Route(name="scan_qr", path="battery/scan/qr")
     * @return Response
     */
    public function scanQrAction(): Response
    {
        /** If Logged In - redirect to Dashboard */
        if ($this->userService->isAuthenticated()) {
            return new RedirectResponse($this->urlGenerator->generate('home'));
        }

        return $this->render(
            'public_templates/scan1.html.twig'
        );

    }

    /**
     * @Route(name="scan_qr-extra", path="battery/scan/qr/extra")
     * @return Response
     */
    public function scanQrExtraAction(): Response
    {
        /** If Logged In - redirect to Dashboard */
        if ($this->userService->isAuthenticated()) {
            return new RedirectResponse($this->urlGenerator->generate('home'));
        }

        return $this->render(
            'public_templates/scan.html.twig'
        );
    }

    /**
     * @Route(name="ip_header", path="api/header/ip", methods={"GET"})
     * @return JsonResponse
     */
    public function getIpFromServerObject(): JsonResponse
    {
        return new JsonResponse(
            [
                'data' => CustomHelper::get_ip_address()
            ]
        );
    }

    /**
     * @Route(name="ip_api_address", path="api/ip", methods={"GET"})
     * @return JsonResponse
     */
    public function getIpFromAPI(): JsonResponse
    {
        return new JsonResponse(
            [
                'data' => CustomHelper::sendCurlRequestToGetIp(Request::METHOD_GET, ['Content-Type: application/json'])
            ]
        );
    }

    /**
     * @Route(name="ip_api", path="api/ip/detail", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     */
    public function getIpDetail(Request $request): JsonResponse
    {
        $ip = $request->query->get('ip', '115.186.47.254');

        return new JsonResponse(
            [
                'data' => CustomHelper::get_ip_details($ip)
            ]
        );
    }
}