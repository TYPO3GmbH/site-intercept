<?php

namespace App\Security;

use App\Entity\Redirect;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class RedirectVoter extends Voter
{
    // these strings are just invented: you can use anything
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';

    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports($attribute, $subject)
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)) {
            return false;
        }

        // only vote on Redirect objects inside this voter
        if (!$subject instanceof Redirect) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        // you know $subject is a Redirect object, thanks to supports
        /** @var Redirect $redirect */
        $redirect = $subject;

        switch ($attribute) {
            case self::VIEW:
                return $this->canView();
            case self::EDIT:
                return $this->canEdit($redirect, $user);
            case self::DELETE:
                return $this->canDelete();
            default:
                throw new \LogicException('This code should not be reached!');
        }
    }

    private function canView(): bool
    {
        return $this->security->isGranted('ROLE_REDIRECTS');
    }

    private function canEdit(Redirect $redirect, User $user): bool
    {
        return ($this->security->isGranted('ROLE_REDIRECTS') && $user === $redirect->getOwner())
            || $this->security->isGranted('ROLE_ADMIN');
    }

    private function canDelete(): bool
    {
        return $this->security->isGranted('ROLE_ADMIN');
    }
}