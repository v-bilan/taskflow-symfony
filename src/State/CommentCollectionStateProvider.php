<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use App\Repository\CommentRepository;
use Doctrine\ORM\QueryBuilder;
use Override;
use Symfony\Bundle\SecurityBundle\Security;

class CommentCollectionStateProvider extends AbstractCollectionStateProvider
{
    public function __construct(
        private readonly CommentRepository $commentRepository,
        private readonly Security $security,
        private readonly Pagination $pagination
    ) {
        parent::__construct($pagination);
    }

    #[Override]
    protected function getQueryBuilder(Operation $operation, array $uriVariables = [], array $context = []): QueryBuilder
    {
        $user = $this->security->getUser();
        $qb = $this->commentRepository->createQueryBuilder('c');
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $qb->andWhere('c.author = :author')
            ->setParameter('author', $user)
            ->andWhere('c.deletedAt IS NULL');
        }
        return $qb;
    }
   
}
