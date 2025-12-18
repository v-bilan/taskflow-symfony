<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use App\Entity\Task;
use App\Repository\CommentRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\QueryBuilder;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TaskCommentCollectionStateProvider extends CommentCollectionStateProvider
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        CommentRepository $commentRepository,
        Security $security,
        Pagination $pagination
    ) {
        parent::__construct($commentRepository, $security, $pagination);
    }

    #[Override]
    protected function getQueryBuilder(Operation $operation, array $uriVariables = [], array $context = []) : QueryBuilder
    {
        $task = $this->taskRepository->find((int)($uriVariables['id'] ?? 0));
        if (! $task) {
            throw new NotFoundHttpException();
        }
        $qb = parent::getQueryBuilder($operation, $uriVariables, $context);
        $qb = $qb
            ->andWhere('c.task = :task')
            ->setParameter('task', $task);
        return $qb;
    }
}
