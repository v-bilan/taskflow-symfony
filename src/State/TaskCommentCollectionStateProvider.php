<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use App\Repository\CommentRepository;
use App\Repository\TaskRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TaskCommentCollectionStateProvider extends AbstractCollectionStateProvider
{
    public function __construct(
        private CommentRepository $commentRepository,
        private TaskRepository $taskRepository,
        private Security $security,
        Pagination $pagination
    ) {
        parent::__construct($pagination);
    }

    protected function getQueryBuilder(Operation $operation, array $uriVariables = [], array $context = []) 
    {
        
        $task = $this->taskRepository->find(intval($uriVariables['id']));
        if (! $task) {
            throw new NotFoundHttpException();
        }
        $user = $this->security->getUser();
        $qb = $this->commentRepository->createQueryBuilder('c')
            ->andWhere('c.task = :task')
            ->setParameter('task', $task);
            
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $qb->andWhere('c.author = :author')
            ->setParameter('author', $user);
        }
        return $qb;
    }
}
