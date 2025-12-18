<?php

namespace App\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Comment;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator('api_platform.doctrine.orm.state.persist_processor')]
class CommentRestoreProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $innerProcessor,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (
            $data instanceof Comment 
            && $operation instanceof Patch
            && $operation->getUriTemplate() === '/comments/{id}/restore') {
            $data->setDeletedAt(null);
        }
        
        return $this->innerProcessor->process($data, $operation, $uriVariables, $context);
    }
}
