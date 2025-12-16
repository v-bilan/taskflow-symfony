<?php

namespace App\State;

use ApiPlatform\Metadata\Exception\AccessDeniedException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Comment;
use App\Entity\Task;
use App\Security\Voter\TaskVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsDecorator('api_platform.doctrine.orm.state.persist_processor')]
class CommentSetOwnerAndTaskProcessor implements ProcessorInterface
{
    public function __construct( 
        private EntityManagerInterface $entityManager,
        private ProcessorInterface $innerProcessor,       
        private Security $security        
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($data instanceof Comment && $operation instanceof Post) {

            $id = (int) ($uriVariables['id'] ?? 0);
            $task = $this->entityManager->getRepository(Task::class)->find($id);
            if (!$task) {
                throw new NotFoundHttpException('Task not found');
            }
            if (!$this->security->isGranted(TaskVoter::EDIT, $task)) {
                throw new AccessDeniedException();
            }
            if (!$data->getAuthor() && $this->security->getUser()) {
                $data->setAuthor($this->security->getUser());
            }    
            if (!$data->getTask()) {
                $data->setTask($task);
            }
            
        }
       
        return $this->innerProcessor->process($data, $operation, $uriVariables, $context);      
    }
}
