<?php

namespace App\State;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator('api_platform.doctrine.orm.state.item_provider')]
class CommentStateProvider implements ProviderInterface
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        private Security $security,
        private ProviderInterface $innerProvider
    ) {
       
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (($operation instanceof Get || $operation instanceof Patch) && 
            $operation->getClass() == Comment::class && 
            $this->security->isGranted('ROLE_ADMIN')
        ) {
            $this->entityManager->getFilters()->disable('soft_delete');
        }
        return $this->innerProvider->provide($operation, $uriVariables, $context);
    }
   
}
