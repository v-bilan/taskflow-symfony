<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\TaskRepository;
use Symfony\Bundle\SecurityBundle\Security;

class TaskCollectionStateProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private TaskRepository $taskRepository
    ) {       
    }
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {        
        $user = $this->security->getUser();
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return $this->taskRepository->findAll();
        }
       
        return $this->taskRepository->findBy(['owner'=> $user]);
        
    }
}
