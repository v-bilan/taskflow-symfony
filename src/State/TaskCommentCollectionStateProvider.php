<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use App\Entity\Comment;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TaskCommentCollectionStateProvider extends AbstractCollectionStateProvider
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        Pagination $pagination
    ) {
        parent::__construct($pagination);
    }
    #[Override]
    protected function getQueryBuilder(Operation $operation, array $uriVariables = [], array $context = []) : QueryBuilder
    {
        
        $task = $this->entityManager->getRepository(Task::class)->find(intval($uriVariables['id']));
        
        if (! $task) {
            throw new NotFoundHttpException();
        }
        $user = $this->security->getUser();
        $qb = $this->entityManager->getRepository(Comment::class)->createQueryBuilder('c')
            ->andWhere('c.task = :task')
            ->setParameter('task', $task);
            
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $qb->andWhere('c.author = :author')
            ->setParameter('author', $user);
        } else {
            // Admins can see all comments, including soft-deleted ones
            $this->entityManager->getFilters()->disable('soft_delete');
        }
        return $qb;
    }
}
