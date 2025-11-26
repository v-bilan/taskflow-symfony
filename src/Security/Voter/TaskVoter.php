<?php

namespace App\Security\Voter;

use App\Entity\Task;
use App\Repository\TaskRepository;
use Doctrine\ORM\Query\Expr\Func;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class TaskVoter extends Voter
{
    public const EDIT = 'TASK_EDIT';
    public function __construct(
        private Security $security
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT])
            && $subject instanceof Task;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();


        if (!$user instanceof UserInterface) {
            return false;
        }

        if ($this->security->isGrantedForUser($user, 'ROLE_ADMIN')) {
            return true;
        }


        switch ($attribute) {
            case self::EDIT:
                return $subject?->getOwner() == $user;
                break;
        }

        return false;
    }
}
