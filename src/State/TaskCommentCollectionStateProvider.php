<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use App\Entity\Task;
use Doctrine\ORM\QueryBuilder;
use Override;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TaskCommentCollectionStateProvider extends CommentCollectionStateProvider
{

    #[Override]
    protected function getQueryBuilder(Operation $operation, array $uriVariables = [], array $context = []) : QueryBuilder
    {
        $task = $this->entityManager->getRepository(Task::class)->find((int)($uriVariables['id'] ?? 0));
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
