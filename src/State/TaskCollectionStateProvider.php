<?php

namespace App\State;

use ApiPlatform\Doctrine\Orm\Extension\DoctrinePaginatorFactory;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use App\Repository\TaskRepository;
use Symfony\Bundle\SecurityBundle\Security;

class TaskCollectionStateProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private TaskRepository $taskRepository,
        private Pagination $pagination        
    ) {       
    }
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {        

        $page = $this->pagination->getPage($context);
        $itemsPerPage = $this->pagination->getLimit($operation, $context);
        $offset = $this->pagination->getOffset($operation, $context);

        $user = $this->security->getUser();
        $qb = $this->taskRepository->createQueryBuilder('t');
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $qb->andWhere('t.owner = :owner')
            ->setParameter('owner', $user);
        }

        $doctrinePaginatorFactory = new DoctrinePaginatorFactory();
        $doctrinePaginator = $doctrinePaginatorFactory->getPaginator($qb->getQuery(), true);
        
        $doctrinePaginator->getQuery()->setFirstResult($offset)->setMaxResults($itemsPerPage);

        return $doctrinePaginator;
        
    }
}
