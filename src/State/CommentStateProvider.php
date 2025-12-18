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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        $result = $this->innerProvider->provide($operation, $uriVariables, $context);
        if ( 
            $result instanceof Comment
            && !$this->security->isGranted('ROLE_ADMIN')
            && $result->isSoftDeleted()){
                throw new NotFoundHttpException();
            }
          return $result;
    }
   
}
