<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Override;
use Symfony\Bundle\SecurityBundle\Security;

class CommentCollectionStateProvider extends AbstractCollectionStateProvider
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        private Security $security,
        Pagination $pagination
    ) {
        parent::__construct($pagination);
    }

    #[Override]
    protected function getQueryBuilder(Operation $operation, array $uriVariables = [], array $context = []): QueryBuilder
    {
        $user = $this->security->getUser();
        $qb = $this->entityManager->getRepository(Comment::class)->createQueryBuilder('c');
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $qb->andWhere('c.author = :author')
            ->setParameter('author', $user);
        } else {
            $this->entityManager->getFilters()->disable('soft_delete');
        }
        return $qb;
    }
   
}
