<?php

namespace App\Security\Voter;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class CommentVoter extends Voter
{
    public const EDIT = 'COMMENT_EDIT';
    public const VIEW = 'COMMENT_VIEW';
    public const DELETE = 'COMMENT_DELETE';

    public function __construct(
        private Security $security
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW, self::DELETE])
            && $subject instanceof \App\Entity\Comment;
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
            case self::DELETE:
                if ($subject->isSoftDeleted()) {
                    return false;
                }
            case self::VIEW:
            case self::EDIT:
                return $subject?->getAuthor() == $user;
                break;
        }

        return false;
    }
}
