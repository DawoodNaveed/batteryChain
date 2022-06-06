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
        $user = $manager->getRepository('App:User')->findBy(['email' => 'admin@battery-chain.com']);

        if (empty($user)) {
            $user = new User();
            $user->setFirstName('Batter');
            $user->setLastName('Chain');
            $user->setEmail('admin@battery-chain.com');
            $user->setIsVerified(true);
            $user->setPassword($this->userPasswordHasherInterface->hashPassword($user, 'coeus123@'));
            $user->setRoles([
                'ROLE_SUPER_ADMIN'
            ]);

            $manager->persist($user);
            $manager->flush();
        }
        // TODO: Implement load() method.
    }
}