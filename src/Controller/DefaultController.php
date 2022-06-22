<?php

namespace App\Controller;

use App\Entity\Battery;
use App\Form\BatteryDetailFormType;
use App\Service\BatteryService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class DefaultController
 * @package App\Controller
 * @property UserService userService
 * @property UrlGeneratorInterface urlGenerator
 * @property BatteryService batteryService
 */
class DefaultController extends AbstractController
{
    /**
     * DefaultController constructor.
     * @param UserService $userService
     * @param UrlGeneratorInterface $urlGenerator
     * @param BatteryService $batteryService
     */
    public function __construct(
        UserService $userService,
        UrlGeneratorInterface $urlGenerator,
        BatteryService $batteryService
    ) {
        $this->userService = $userService;
        $this->urlGenerator = $urlGenerator;
        $this->batteryService = $batteryService;
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
            $formData = $form->getData();
            $serialNumber = $formData['battery'] ?? null;

            if (empty($serialNumber)) {
                $this->addFlash('danger', 'Kindly Insert Valid Battery Serial Number!');
                return new RedirectResponse('/');
            }

            /** @var Battery|null $battery */
            $battery = $this->batteryService
                ->fetchBatteryBySerialNumber($serialNumber);

            if (empty($battery)) {
                $this->addFlash('danger', 'Battery does not exist!');
                return new RedirectResponse('/');
            }

            return $this->render(
                'public_templates/detail_view.html.twig', [
                    'battery' => $battery,
                    'path' => '#',
                    'downloadPath' => '#'
                ]
            );
        }

        return $this->render(
            'public_templates/detail_form_view.html.twig',
            array(
                'searchForm' => $form->createView(),
                'public' => true
            )
        );
    }

}