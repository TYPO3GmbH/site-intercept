<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Security\LdapUserProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Ldap\Adapter\CollectionInterface;
use Symfony\Component\Ldap\Adapter\QueryInterface;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Partially taken from symfony base LdapUserProvider
 */
class LdapUserProviderTest extends TestCase
{
    /**
     * @test
     * @expectedException \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     */
    public function refreshUserThrowExceptionWithWrongUserClass()
    {
        $ldapProphecy = $this->prophesize(LdapInterface::class);
        $subject = new LdapUserProvider($ldapProphecy->reveal(), '');
        $subject->refreshUser($this->prophesize(UserInterface::class)->reveal());
    }

    /**
     * @test
     */
    public function refreshUserReturnsNewUser()
    {
        $ldapProphecy = $this->prophesize(LdapInterface::class);
        /** @var ObjectProphecy|User $userProphecy */
        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getUsername()->willReturn('myUsername')->shouldBeCalled();
        $userProphecy->getDisplayName()->willReturn('myDisplayName')->shouldBeCalled();
        $userProphecy->getRoles()->willReturn([])->shouldBeCalled();
        $subject = new LdapUserProvider($ldapProphecy->reveal(), '');
        $result = $subject->refreshUser($userProphecy->reveal());
        $this->assertInstanceOf(User::class, $result);
    }

    /**
     * @test
     */
    public function supportsClassReturnsTrueWithUserClass()
    {
        $ldapProphecy = $this->prophesize(LdapInterface::class);
        $subject = new LdapUserProvider($ldapProphecy->reveal(), '');
        $this->assertTrue($subject->supportsClass(User::class));
    }

    /**
     * @test
     */
    public function loadUserByUsernameLoadsUserWithRoles()
    {
        /** @var ObjectProphecy|LdapInterface $ldapProphecy */
        $ldapProphecy = $this->prophesize(LdapInterface::class);
        $queryProphecy = $this->prophesize(QueryInterface::class);
        $ldapProphecy->bind(Argument::cetera())->shouldBeCalled();
        $ldapProphecy->escape(Argument::cetera())->willReturnArgument(0);
        $ldapProphecy->query(Argument::cetera())->willReturn($queryProphecy->reveal());
        $entryProphecy = $this->prophesize(Entry::class);
        $queryProphecy->execute()->willReturn([$entryProphecy->reveal()]);
        $entryProphecy->hasAttribute('uid')->willReturn(true);
        $entryProphecy->getAttribute('uid')->willReturn(['myUser']);
        $entryProphecy->hasAttribute('displayName')->willReturn(true);
        $entryProphecy->getAttribute('displayName')->willReturn(['myDisplayName']);
        $entryProphecy->hasAttribute('isMemberOf')->willReturn(true);
        $entryProphecy->getAttribute('isMemberOf')->willReturn(['typo3.com-gmbh']);
        $subject = new LdapUserProvider($ldapProphecy->reveal(), '');
        $result = $subject->loadUserByUsername('uid');
        $this->assertEquals('myUser', $result->getUsername());
        $this->assertEquals('myDisplayName', $result->getDisplayName());
        $this->assertEquals(['typo3.com-gmbh' => 'ROLE_ADMIN'], $result->getRoles());
    }

    /**
     * @test
     */
    public function loadUserByUsernameLoadsUserIfNoIsMemberOfAttributeIsSet()
    {
        /** @var ObjectProphecy|LdapInterface $ldapProphecy */
        $ldapProphecy = $this->prophesize(LdapInterface::class);
        $queryProphecy = $this->prophesize(QueryInterface::class);
        $ldapProphecy->bind(Argument::cetera())->shouldBeCalled();
        $ldapProphecy->escape(Argument::cetera())->willReturnArgument(0);
        $ldapProphecy->query(Argument::cetera())->willReturn($queryProphecy->reveal());
        $entryProphecy = $this->prophesize(Entry::class);
        $queryProphecy->execute()->willReturn([$entryProphecy->reveal()]);
        $entryProphecy->hasAttribute('uid')->willReturn(true);
        $entryProphecy->getAttribute('uid')->willReturn(['myUser']);
        $entryProphecy->hasAttribute('displayName')->willReturn(true);
        $entryProphecy->getAttribute('displayName')->willReturn(['myDisplayName']);
        $entryProphecy->hasAttribute('isMemberOf')->willReturn(false);
        $subject = new LdapUserProvider($ldapProphecy->reveal(), '');
        $result = $subject->loadUserByUsername('uid');
        $this->assertEquals('myUser', $result->getUsername());
        $this->assertEquals('myDisplayName', $result->getDisplayName());
        $this->assertEquals([], $result->getRoles());
    }

    /**
     * @test
     * @expectedException \Symfony\Component\Security\Core\Exception\InvalidArgumentException
     */
    public function loadUserByUsernameThrowsIfUidAttributeIsSetTwice()
    {
        /** @var ObjectProphecy|LdapInterface $ldapProphecy */
        $ldapProphecy = $this->prophesize(LdapInterface::class);
        $queryProphecy = $this->prophesize(QueryInterface::class);
        $ldapProphecy->bind(Argument::cetera())->shouldBeCalled();
        $ldapProphecy->escape(Argument::cetera())->willReturnArgument(0);
        $ldapProphecy->query(Argument::cetera())->willReturn($queryProphecy->reveal());
        $entryProphecy = $this->prophesize(Entry::class);
        $queryProphecy->execute()->willReturn([$entryProphecy->reveal()]);
        $entryProphecy->hasAttribute('uid')->willReturn(true);
        $entryProphecy->getAttribute('uid')->willReturn(['myUser', 'myOtherUser']);
        $subject = new LdapUserProvider($ldapProphecy->reveal(), '');
        $subject->loadUserByUsername('uid');
    }

    /**
     * @test
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function loadUserByUsernameFailsIfCantConnectToLdap()
    {
        $ldap = $this->getMockBuilder(LdapInterface::class)->getMock();
        $ldap
            ->expects($this->once())
            ->method('bind')
            ->willThrowException(new ConnectionException())
        ;

        $provider = new LdapUserProvider($ldap, 'ou=MyBusiness,dc=symfony,dc=com');
        $provider->loadUserByUsername('foo');
    }

    /**
     * @test
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function loadUserByUsernameFailsIfNoLdapEntries()
    {
        $result = $this->getMockBuilder(CollectionInterface::class)->getMock();
        $query = $this->getMockBuilder(QueryInterface::class)->getMock();
        $query
            ->expects($this->once())
            ->method('execute')
            ->will($this->returnValue($result))
        ;
        $result
            ->expects($this->once())
            ->method('count')
            ->will($this->returnValue(0))
        ;
        $ldap = $this->getMockBuilder(LdapInterface::class)->getMock();
        $ldap
            ->expects($this->once())
            ->method('escape')
            ->will($this->returnValue('foo'))
        ;
        $ldap
            ->expects($this->once())
            ->method('query')
            ->will($this->returnValue($query))
        ;

        $provider = new LdapUserProvider($ldap, 'ou=MyBusiness,dc=symfony,dc=com');
        $provider->loadUserByUsername('foo');
    }

    /**
     * @test
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function loadUserByUsernameFailsIfMoreThanOneLdapEntry()
    {
        $result = $this->getMockBuilder(CollectionInterface::class)->getMock();
        $query = $this->getMockBuilder(QueryInterface::class)->getMock();
        $query
            ->expects($this->once())
            ->method('execute')
            ->will($this->returnValue($result))
        ;
        $result
            ->expects($this->once())
            ->method('count')
            ->will($this->returnValue(2))
        ;
        $ldap = $this->getMockBuilder(LdapInterface::class)->getMock();
        $ldap
            ->expects($this->once())
            ->method('escape')
            ->will($this->returnValue('foo'))
        ;
        $ldap
            ->expects($this->once())
            ->method('query')
            ->will($this->returnValue($query))
        ;

        $provider = new LdapUserProvider($ldap, 'ou=MyBusiness,dc=symfony,dc=com');
        $provider->loadUserByUsername('foo');
    }

    /**
     * @test
     * @expectedException \Symfony\Component\Security\Core\Exception\InvalidArgumentException
     */
    public function loadUserByUsernameFailsIfMoreThanOneLdapPasswordsInEntry()
    {
        $result = $this->getMockBuilder(CollectionInterface::class)->getMock();
        $query = $this->getMockBuilder(QueryInterface::class)->getMock();
        $query
            ->expects($this->once())
            ->method('execute')
            ->will($this->returnValue($result))
        ;
        $ldap = $this->getMockBuilder(LdapInterface::class)->getMock();
        $result
            ->expects($this->once())
            ->method('offsetGet')
            ->with(0)
            ->will($this->returnValue(new Entry(
                'foo',
                [
                    'sAMAccountName' => ['foo'],
                    'userpassword' => ['bar', 'baz'],
                ]
            )))
        ;
        $result
            ->expects($this->once())
            ->method('count')
            ->will($this->returnValue(1))
        ;
        $ldap
            ->expects($this->once())
            ->method('escape')
            ->will($this->returnValue('foo'))
        ;
        $ldap
            ->expects($this->once())
            ->method('query')
            ->will($this->returnValue($query))
        ;

        $provider = new LdapUserProvider($ldap, 'ou=MyBusiness,dc=symfony,dc=com', null, null, [], 'sAMAccountName', '({uid_key}={username})', 'userpassword');
        $this->assertInstanceOf(
            'Symfony\Component\Security\Core\User\User',
            $provider->loadUserByUsername('foo')
        );
    }

    /**
     * @test
     * @expectedException \Symfony\Component\Security\Core\Exception\InvalidArgumentException
     */
    public function loadUserByUsernameFailsIfEntryHasNoPasswordAttribute()
    {
        $result = $this->getMockBuilder(CollectionInterface::class)->getMock();
        $query = $this->getMockBuilder(QueryInterface::class)->getMock();
        $query
            ->expects($this->once())
            ->method('execute')
            ->will($this->returnValue($result))
        ;
        $ldap = $this->getMockBuilder(LdapInterface::class)->getMock();
        $result
            ->expects($this->once())
            ->method('offsetGet')
            ->with(0)
            ->will($this->returnValue(new Entry(
                'foo',
                [
                    'sAMAccountName' => ['foo'],
                ]
            )))
        ;
        $result
            ->expects($this->once())
            ->method('count')
            ->will($this->returnValue(1))
        ;
        $ldap
            ->expects($this->once())
            ->method('escape')
            ->will($this->returnValue('foo'))
        ;
        $ldap
            ->expects($this->once())
            ->method('query')
            ->will($this->returnValue($query))
        ;

        $provider = new LdapUserProvider($ldap, 'ou=MyBusiness,dc=symfony,dc=com', null, null, [], 'sAMAccountName', '({uid_key}={username})', 'userpassword');
        $this->assertInstanceOf(
            'Symfony\Component\Security\Core\User\User',
            $provider->loadUserByUsername('foo')
        );
    }
}
