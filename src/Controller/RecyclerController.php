<?php

namespace App\Controller;

use App\Entity\Country;
use App\Entity\User;
use App\Form\AvailableRecyclerFormType;
use App\Service\CountryService;
use App\Service\RecyclerService;
use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

/**
 * Class RecyclerController
 * @package App\Controller
 * @property CountryService countryService
 * @property RecyclerService recyclerService
 * @property Security security
 * @property EntityManagerInterface entityManager
 */
class RecyclerController extends CRUDController
{
    /**
     * RecyclerController constructor.
     * @param CountryService $countryService
     * @param RecyclerService $recyclerService
     * @param Security $security
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(CountryService $countryService, RecyclerService $recyclerService, Security $security, EntityManagerInterface $entityManager)
    {
        $this->countryService = $countryService;
        $this->recyclerService = $recyclerService;
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function attachAction(Request $request): Response
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $manufacturer = $user->getManufacturer();
        $countries = $this->countryService->getCountries();
        $recyclers = $this->recyclerService->getRecyclersByCountryId(array_values($countries)[0]);
        $form = $this->createForm(AvailableRecyclerFormType::class, null, [
            'countries' => $countries,
            'recyclers' => $recyclers
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $recyclers = $this->recyclerService->getRecyclerByIds($formData['recycler']);
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
}