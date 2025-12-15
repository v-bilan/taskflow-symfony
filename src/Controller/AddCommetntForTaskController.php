<?php

namespace App\Controller;

use ApiPlatform\Metadata\Exception\AccessDeniedException;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Entity\Comment;
use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface as ValidatorValidatorInterface;

final class AddCommetntForTaskController extends AbstractController
{
    public function __invoke(
        Task $task,
        Request $request,
        ValidatorValidatorInterface $validator,
        EntityManagerInterface $entityManager
    ): Comment {
        if (! $this->isGranted('ADD_COMMENT', $task)) {
            throw new AccessDeniedException();
        }
        $data = json_decode($request->getContent());
       
        $comment = new Comment();
        $comment->setTask($task);
        $comment->setAuthor($this->getUser());
        $comment->setMessage($data?->message);

        $errors = $validator->validate($comment);
        
        if (count($errors)) {
            throw new ValidationException($errors);
        }
        $entityManager->persist($comment);
        $entityManager->flush();

        return $comment; 
    }
}
