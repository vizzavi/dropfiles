<?php

namespace App\Security;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\User\UserInterface;

class Token extends AbstractToken
{
    private string $tokenValue;

    public function __construct(string $tokenValue, UserInterface $user, array $roles = [])
    {
        parent::__construct($roles);

        $this->tokenValue = $tokenValue;
        $this->setUser($user);
    }

    public function getCredentials()
    {
        return $this->tokenValue;
    }

    public function isAuthenticated(): bool
    {
        return true;
    }
}