<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator('api_platform.doctrine.orm.state.remove_processor')]
class CommentSoftDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $innerProcessor,
        private readonly EntityManagerInterface $entityManager,
    ) {}
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof \App\Entity\Comment) {
            $data->softDelete();
            $this->entityManager->flush();
            return null;
        }
        
        return $this->innerProcessor->process($data, $operation, $uriVariables, $context);
    }
}
