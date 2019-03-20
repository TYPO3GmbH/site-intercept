<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Bundle;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Bundle that injects an own LDAP user provider over the
 * default LdapUserProvider from symfony.
 */
class LdapBundle extends Bundle implements CompilerPassInterface
{
    /**
     * Register self as compiler class. This is called once on cache rebuild by
     * symfony, NOT for each request and NOT for each functional test run.
     *
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new $this);
    }

    /**
     * Create a list of services that can be doubled and park their
     * service names in the container builder as parameter. This is called
     * exactly once per full functional test run.
     *
     * @param ContainerBuilder $containerBuilder
     */
    public function process(ContainerBuilder $containerBuilder)
    {
        $definition = $containerBuilder->getDefinition('security.user.provider.ldap');
        $definition->setClass('App\Security\LdapUserProvider');
    }
}
