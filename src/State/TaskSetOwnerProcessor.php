<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Task;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator('api_platform.doctrine.orm.state.persist_processor')]
class TaskSetOwnerProcessor implements ProcessorInterface
{
    public function __construct( 
        private ProcessorInterface $innerProcessor,       
        private Security $security        
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []) 
    {
        if ($data instanceof Task && $data->getOwner() === null && $this->security->getUser() && $operation instanceof Post) {
            $data->setOwner($this->security->getUser());          
        }
        return $this->innerProcessor->process($data, $operation, $uriVariables, $context);        
    }
}
