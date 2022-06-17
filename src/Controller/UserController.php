<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UpdateProfileFormType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class UserController
 * @package App\Controller
 * @property LoggerInterface $logger
 * @property EntityManagerInterface entityManager
 */
class UserController extends AbstractController
{
    /**
     * UserController constructor.
     * @param LoggerInterface $logger
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/home", name="home")
     */
    public function index(Request $request): Response
    {
        // usually you'll want to make sure the user is authenticated first,
        // see "Authorization" below
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // returns your User object, or null if the user is not authenticated
        // use inline documentation to tell your editor your exact User class
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $roles = $user->getRoles();

        return $this->redirectToRoute('sonata_admin_dashboard');
    }

    /**
     * @Route(path="\update_profile", name="update_profile")
     * @param Request $request
     * @return Response
     */
    public function updateProfile(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(UpdateProfileFormType::class, $user->getManufacturer(), [
            'firstname' => $user->getFirstName(),
            'lastname' => $user->getLastName(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $firstname = $form['firstname']->getData();
            $lastname = $form['lastname']->getData();
            $formData->setName($firstname . ' ' . $lastname);
            $user->setFirstName($firstname);
            $user->setLastName($lastname);
            $this->entityManager->persist($formData);
            $this->entityManager->flush();

            return new RedirectResponse($this->generateUrl('sonata_admin_dashboard'));
        }

        return $this->render(
            'user/update-profile.html.twig',[
                'updateProfileForm' => $form->createView()
            ]
        );
    }
}