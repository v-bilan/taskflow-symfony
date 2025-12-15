<?php

namespace App\DataFixtures;

use App\Factory\CommentFactory;
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

        $adminTask = TaskFactory::createOne(
            ['owner' => $adminUser]
        );

        CommentFactory::createOne(
            [
                'author' => $adminUser,
                'task'=> $adminTask
            ]
            );

        TaskFactory::createMany(30);
        CommentFactory::createMany(100);

        $manager->flush();
    }


}
