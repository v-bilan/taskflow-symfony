<?php

namespace App\DataFixtures;

use App\Factory\ApiTokenFactory;
use App\Factory\TaskFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);
        $adminUser = UserFactory::createOne(
            [
                'email' => 'admin@taskflow.com',
                'roles' => ['ROLE_ADMIN'],
                'plainPassword' => 'admin123',
                'name' => 'Admin User',
            ]
        );

        UserFactory::createMany(10);

        TaskFactory::createOne(
            ['owner' => $adminUser]
        );

        TaskFactory::createMany(50);

        ApiTokenFactory::createOne(
            [
                'owner' => $adminUser,
                'expiredAt' => null
            ]
        );

        ApiTokenFactory::createOne(
            [
                'token' => '12345',
                'owner' => $adminUser,
                'expiredAt' => null
            ]
        );

        ApiTokenFactory::createMany(30);

        $manager->flush();
    }


}
