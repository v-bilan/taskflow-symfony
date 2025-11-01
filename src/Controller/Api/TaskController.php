<?php

namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class TaskController extends AbstractController
{
    #[Route('/api/tasks', name: 'app_api_task', methods:['GET'])]
    public function getUserTasks(#[CurrentUser()] User $user): Response
    {

        $tasks = $user->getTasks();

        return $this->json(
            data: [
                'data' => $tasks,
                'total' => count($tasks)
            ],
            context: [
                'groups' => ['task:list']
            ]
        );
    }
}
