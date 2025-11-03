<?php

namespace App\Controller\Api;

use App\Entity\Task;
use App\Entity\User;
use App\Repository\TaskRepository;
use App\Security\Voter\TaskVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class TaskController extends AbstractController
{
    #[Route('/api/tasks', name: 'app_api_task_list', methods:['GET'])]
    public function getUserTasks(): Response
    {
        /**
         * @var User $user
         */
        $user = $this->getUser();

        if (!$user) {
            return $this->getUnautorizedResponse();
        }

        $tasks = $user->getTasks();

        return $this->json(
            data: [
                'status' => 'ok',
                'data' => $tasks,
                'total' => count($tasks)
            ],
            context: [
                'groups' => ['task:list']
            ]
        );
    }

    #[Route('/api/tasks/{id}', requirements:['id' => Requirement::DIGITS],  name: 'app_api_task_show', methods:['GET'])]
    public function getTask(int $id, TaskRepository $taskRepository): Response
    {
        if (!$this->getUser())
        {
           return $this->getUnautorizedResponse();
        }

        $task = $taskRepository->find($id);

        if (!$task) {
            return $this->json([
                'status' => 'error',
                'errors' => ['Task not found']
            ], 404);
        }
/*
        if (! $this->isGranted(TaskVoter::EDIT, $task)) {
            return $this->json([
                'status' => 'error',
                'errors' => ['Forbidden']
            ], 403);
        }
            */

        return $this->json(
            data: [
                'status' => 'ok',
                'data' => $task
            ],
            context: [
                'groups' => ['task:show']
            ]
        );
    }

    #[Route('/api/tasks/{id}', requirements:['id' => Requirement::DIGITS], name: 'app_api_task_delete', methods:['DELETE'])]
    public function deleteTask(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ) {
        if (!$this->getUser())
        {
           return $this->getUnautorizedResponse();
        }
        $task = $entityManager->getRepository(Task::class)->find($id);

        if (!$task) {
            return $this->json([
                'status' => 'error',
                'errors' => ['Task not found']
            ], 404);
        }

        if (! $this->isGranted(TaskVoter::EDIT, $task)) {
            return $this->json([
                'status' => 'error',
                'errors' => ['Forbidden']
            ], 403);
        }
        $entityManager->remove($task);
        $entityManager->flush();

        return $this->json(
            [
                'status' => 'ok',
            ],
            200
        );
    }

    #[Route('/api/tasks', name: 'app_api_task_create', methods:['POST'])]
    public function createTask(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        if (!$this->getUser())
        {
           return $this->getUnautorizedResponse();
        }

        /**
         * @var Task $task
         */

        $task = $serializer->deserialize($request->getContent(), Task::class, 'json', context: [
            'groups' => ['task:save']
        ]);

        $task->setOwner($this->getUser());
        $errors = $validator->validate($task);

        if (count($errors)) {
            $errorsMessages = [];
            foreach ($errors as $error) {
                $errorsMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(
                [
                    'status' => 'error' ,
                    'errors' => $errorsMessages,
                ],
                400
            );
        }
        $entityManager->persist($task);
        $entityManager->flush();
        return $this->json(
            [
                'status' => 'ok',
                'data' => ['id'=> $task->getId()]
            ],
            201
        );

    }

    #[Route('/api/tasks/{id}', requirements:['id' => Requirement::DIGITS], name: 'app_api_task_update', methods:['PATCH'])]
    public function updateTask(
        int $id,
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        if (!$this->getUser())
        {
           return $this->getUnautorizedResponse();
        }

        $task = $entityManager->getRepository(Task::class)->find($id);

        if (!$task) {
            return $this->json([
                'status' => 'error',
                'errors' => ['Task not found']
            ], 404);
        }

        if (!$this->isGranted(TaskVoter::EDIT, $task)) {
            return $this->json([
                'status' => 'error',
                'errors' => ['Forbidden']
            ], 403);
        }

        /**
         * @var Task $task
         */

        $taskData = json_decode($request->getContent(), true);

        if (isset($taskData['title'])) {
            $task->setTitle($taskData['title']);
        }

        if (isset($taskData['status'])) {
            $task->setStatus($taskData['status']);
        }

        if (isset($taskData['dueDate'])) {
            $task->setDueDate($taskData['dueDate']);
        }

        if (isset($taskData['description'])) {
            $task->setDescription($taskData['description']);
        }

        $errors = $validator->validate($task);

        if (count($errors)) {
            $errorsMessages = [];
            foreach ($errors as $error) {
                $errorsMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(
                [
                    'status' => 'error' ,
                    'errors' => $errorsMessages,
                ],
                400
            );
        }

        $entityManager->flush();
        return $this->json(
            [
                'status' => 'ok',
                'data' => ['id'=> $task->getId()]
            ],
            200
        );

    }

    private function getUnautorizedResponse()
    {
        return $this->json([
            'status' => 'error',
            'errors' => 'Unauthorized'
        ], 401);
    }
}
