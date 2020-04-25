<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Security\Core\Exception\InvalidArgumentException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Basically, the default LDAP user provider to handle isMemberOf attribute for roles
 * and adding display name.
 */
class LdapUserProvider implements UserProviderInterface
{
    private LdapInterface $ldap;

    private string $baseDn;
    private ?string $searchDn = null;
    private ?string $searchPassword = null;
    private array $defaultRoles;
    private string $defaultSearch;

    /**
     * Map ldap isMemberOf attributes to roles
     */
    private array $roleMapping = [
        'typo3.com-intercept-docs' => 'ROLE_DOCUMENTATION_MAINTAINER',
        'typo3.com-gmbh' => 'ROLE_ADMIN',
    ];

    /**
     * @param LdapInterface $ldap
     * @param string $baseDn
     * @param string $searchDn
     * @param string $searchPassword
     * @param array $defaultRoles
     */
    public function __construct(
        LdapInterface $ldap,
        string $baseDn,
        string $searchDn = null,
        $searchPassword = null,
        array $defaultRoles = []
    ) {
        $this->ldap = $ldap;
        $this->baseDn = $baseDn;
        $this->searchDn = $searchDn;
        $this->searchPassword = $searchPassword;
        $this->defaultRoles = $defaultRoles;
        $this->defaultSearch = '(uid={username})';
    }

    /**
     * Creates the user object, assigns roles from isMemberOf attribute
     * and sets display name from LDAP attribute.
     *
     * @param string $username
     * @param Entry $entry
     * @return User
     */
    protected function loadUser($username, Entry $entry): User
    {
        $displayName = '';
        if ($entry->hasAttribute('displayName')) {
            $displayName = $this->getAttributeValue($entry, 'displayName');
        }
        if (!$entry->hasAttribute('isMemberOf')) {
            // If user does not have this attribute at all, he's just ROLE_USER
            return new User($username, null, $displayName, $this->defaultRoles);
        }
        // If user has attribute, assign roles that map
        $isMemberOfValues = $entry->getAttribute('isMemberOf');
        $hasRoles = array_intersect_key($this->roleMapping, array_flip($isMemberOfValues));
        $roles = array_merge($this->defaultRoles, $hasRoles);
        return new User($username, null, $displayName, $roles);
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {
        try {
            $this->ldap->bind($this->searchDn, $this->searchPassword);
            $username = $this->ldap->escape($username, '', LdapInterface::ESCAPE_FILTER);
            $query = str_replace('{username}', $username, $this->defaultSearch);
            $search = $this->ldap->query($this->baseDn, $query);
        } catch (ConnectionException $e) {
            throw new UsernameNotFoundException(sprintf('User "%s" not found.', $username), 0, $e);
        }

        $entries = $search->execute();
        $count = \count($entries);

        if ($count === 0) {
            throw new UsernameNotFoundException(sprintf('User "%s" not found.', $username));
        }

        if ($count > 1) {
            throw new UsernameNotFoundException('More than one user found');
        }

        $entry = $entries[0];

        $username = $this->getAttributeValue($entry, 'uid');

        return $this->loadUser($username, $entry);
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        return new User($user->getUsername(), null, $user->getDisplayName(), $user->getRoles());
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return User::class === $class;
    }

    /**
     * Fetches a required unique attribute value from an LDAP entry.
     *
     * @param Entry|null $entry
     * @param string $attribute
     * @return mixed
     */
    private function getAttributeValue(Entry $entry, $attribute)
    {
        if (!$entry->hasAttribute($attribute)) {
            throw new InvalidArgumentException(sprintf('Missing attribute "%s" for user "%s".', $attribute, $entry->getDn()));
        }

        $values = $entry->getAttribute($attribute);

        if (1 !== \count($values)) {
            throw new InvalidArgumentException(sprintf('Attribute "%s" has multiple values.', $attribute));
        }

        return $values[0];
    }
}
