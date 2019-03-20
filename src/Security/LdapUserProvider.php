<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Security;

use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Security\Core\User\User;

/**
 * Extend default LDAP user provider to handle isMemberOf attribute for roles
 */
class LdapUserProvider extends \Symfony\Component\Security\Core\User\LdapUserProvider
{

    /**
     * Map ldap isMemberOf attributes to roles
     *
     * @var array
     */
    private $roleMapping = [
        'typo3.com-intercept-docs' => 'ROLE_DOCUMENTATION_MAINTAINER',
        'typo3.com-gmbh' => 'ROLE_ADMIN',
    ];

    /**
     * @var array Have this property in this extended class, too
     */
    private $defaultRoles;
    /**
     * @var string Have this property in this extended class, too
     */
    private $passwordAttribute;

    /**
     * @param LdapInterface $ldap
     * @param string        $baseDn
     * @param string        $searchDn
     * @param string        $searchPassword
     * @param array         $defaultRoles
     * @param string        $uidKey
     * @param string        $filter
     * @param string        $passwordAttribute
     */
    public function __construct(LdapInterface $ldap, $baseDn, $searchDn = null, $searchPassword = null, array $defaultRoles = [], $uidKey = 'sAMAccountName', $filter = '({uid_key}={username})', $passwordAttribute = null)
    {
        parent::__construct($ldap, $baseDn, $searchDn, $searchPassword, $defaultRoles, $uidKey, $filter, $passwordAttribute);
        $this->defaultRoles = $defaultRoles;
        $this->passwordAttribute = $passwordAttribute;
    }

    /**
     * @param string $username
     * @param Entry $entry
     * @return User
     */
    protected function loadUser($username, Entry $entry): User
    {
        if (!$entry->hasAttribute('isMemberOf')) {
            // If user does not have this attribute at all, he's just ROLE_USER
            return new User($username, $this->passwordAttribute, $this->defaultRoles);
        }
        // If user has attribute, assign roles that map
        $isMemberOfValues = $entry->getAttribute('isMemberOf');
        $hasRoles = array_intersect_key($this->roleMapping, array_flip($isMemberOfValues));
        $roles = array_merge($this->defaultRoles, $hasRoles);
        return new User($username, $this->passwordAttribute, $roles);
    }
}
