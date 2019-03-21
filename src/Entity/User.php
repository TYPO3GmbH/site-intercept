<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Extend default LDAP user provider to handle isMemberOf attribute for roles
 *
 * @codeCoverageIgnore
 */
class User implements UserInterface
{
    private $username;
    private $password;
    private $roles;

    /**
     * @var string Attribute 'displayName' from LDAP
     */
    private $displayName;

    public function __construct(
        ?string $username,
        ?string $password,
        ?string $displayName,
        array $roles = []
    ) {
        if ('' === $username || null === $username) {
            throw new \InvalidArgumentException('The username cannot be empty.');
        }
        $this->username = $username;
        $this->password = $password;
        $this->displayName = $displayName;
        $this->roles = $roles;
    }

    public function __toString()
    {
        return $this->getUsername();
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Attribute 'displayName' from LDAP
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
    }
}
