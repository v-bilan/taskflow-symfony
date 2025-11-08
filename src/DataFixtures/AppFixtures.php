<?php

namespace App\DataFixtures;

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

        TaskFactory::createMany(1);

        $manager->flush();
    }


}
