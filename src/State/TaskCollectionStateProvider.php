<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use App\Repository\TaskRepository;
use Doctrine\ORM\QueryBuilder;
use Override;
use Symfony\Bundle\SecurityBundle\Security;

class TaskCollectionStateProvider extends AbstractCollectionStateProvider
{
    public function __construct(
        private Security $security,
        private TaskRepository $taskRepository,
        Pagination $pagination        
    ) {   
        parent::__construct($pagination);    
    }
    
    #[Override]
    protected function getQueryBuilder(Operation $operation, array $uriVariables = [], array $context = []): QueryBuilder
    {
        $user = $this->security->getUser();
        $qb = $this->taskRepository->createQueryBuilder('t');
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $qb->andWhere('t.owner = :owner')
            ->setParameter('owner', $user);
        }
        return $qb;
    }
}
