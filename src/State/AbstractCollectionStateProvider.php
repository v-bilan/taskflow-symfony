<?php

namespace App\State;

use ApiPlatform\Doctrine\Orm\Extension\DoctrinePaginatorFactory;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;

abstract class AbstractCollectionStateProvider implements ProviderInterface
{
    public function __construct(
        private Pagination $pagination
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {        

        $page = $this->pagination->getPage($context);
        $limit = $this->pagination->getLimit($operation, $context);
        $offset = $this->pagination->getOffset($operation, $context);

        $qb = $this->getQueryBuilder($operation, $uriVariables, $context);

        $doctrinePaginatorFactory = new DoctrinePaginatorFactory();
        $doctrinePaginator = $doctrinePaginatorFactory->getPaginator($qb->getQuery(), true);
        
        $doctrinePaginator->getQuery()->setFirstResult($offset)->setMaxResults($limit);

        return $doctrinePaginator;
        
    }
    
    abstract protected function getQueryBuilder(Operation $operation, array $uriVariables = [], array $context = []);
}
