<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Class AdminFixtures
 * @package App\DataFixtures
 * @property UserPasswordHasherInterface userPasswordHasherInterface
 */
class AdminFixtures extends Fixture
{
    /**
     * AdminFixtures constructor.
     * @param UserPasswordHasherInterface $userPasswordHasherInterface
     */
    public function __construct(UserPasswordHasherInterface $userPasswordHasherInterface)
    {
        $this->userPasswordHasherInterface = $userPasswordHasherInterface;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $user = $manager->getRepository('App:User')->findBy(['email' => 'admin@battery-chain.info']);

        if (empty($user)) {
            $user = new User();
            $user->setFirstName('Battery');
            $user->setLastName('Chain');
            $user->setEmail('admin@battery-chain.info');
            $user->setIsVerified(true);
            $user->setCreated(new \DateTime('now'));
            $user->setUpdated(new \DateTime('now'));
            $user->setPassword($this->userPasswordHasherInterface->hashPassword($user, '9b8BY3paVQaC94J@'));
            $user->setRoles([
                'ROLE_SUPER_ADMIN'
            ]);

            $manager->persist($user);
            $manager->flush();
        }
        // TODO: Implement load() method.
    }
}