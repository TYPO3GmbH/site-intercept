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
 * TEST bundle for symfony functional testn that allows injecting
 * test doubles for multiple consecutive client requests.
 *
 * Suppose you have a functional test that:
 * * Renders a request
 * * Fetch the form, feeds it with data, submits the form
 *
 * These are two symfony requests, each in their own kernel and container.
 *
 * Say the second request creates a guzzle request to a third party, you
 * may want to substitute this request with a prophecy. To do that, this
 * service should be tagged with 'testDouble' in services_test.yml:
 *
 * services:
 *   App\Client\BambooClient:
 *       tags:
 *           - testDouble
 *
 * Adding this allows this bundle to add a revelation as test double per request:
 *
 * TestDoubleBundle::addProphecy('App\Client\BambooClient', $this->prophesize(BambooClient::class));
 *
 * Note a double has to be injected per request, so two are needed if the test
 * triggers two requests, so call addProphecy() multiple times.
 *
 * Note a service is NOT doubled but an instance of the real object is created if
 * a test does NOT provide (enough) prophecies.
 *
 * @codeCoverageIgnore Not testing testing related classes.
 */
class TestDoubleBundle extends Bundle implements CompilerPassInterface {
    /**
     * An array of service ids with an array of prepared prophecies
     *
     * @var object[][]
     */
    private static $prophecies = [];

    /**
     * Add a prophecy object for a certain service.
     *
     * @param string $serviceId
     * @param object $prophecy
     */
    public static function addProphecy(string $serviceId, object $prophecy)
    {
        if (!isset(static::$prophecies[$serviceId])) {
            static::$prophecies[$serviceId] = [];
        }
        static::$prophecies[$serviceId][] = $prophecy;
    }

    /**
     * Set a prepared prophecy revelation as service object. Objects are
     * taken from static::$prophecies as FiFo.
     *
     * Note if a functional symfony test does multiple requests (eg. render
     * a page first, then fetch the form, feed it and send it: These are two requests),
     * then TWO prophecies have to be added - one per request.
     */
    public function boot()
    {
        foreach ($this->container->getParameter('doubleServices') as $serviceId => $_) {
            if (!empty(static::$prophecies[$serviceId])) {
                $prophecy = array_shift(static::$prophecies[$serviceId]);
                $this->container->set($serviceId, $prophecy->reveal());
            }
        }
    }

    /**
     * Register self as compiler class. This is called once on cache rebuild by
     * symfony, NOT for each request and NOT for each functional test run.
     *
     * @param ContainerBuilder $containerBuilder
     */
    public function build(ContainerBuilder $containerBuilder)
    {
        $containerBuilder->addCompilerPass($this);
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
        $servicesTaggedWithTestDouble = $containerBuilder->findTaggedServiceIds('testDouble');
        $doubledServices = [];
        foreach ($servicesTaggedWithTestDouble as $serviceId => $serviceConfig) {
            $definition = $containerBuilder->getDefinition($serviceId);
            // Mark this service as NOT private, so boot() can set single objects
            $definition->setPrivate(false);
            $containerBuilder->setDefinition($serviceId, $definition);
            $doubledServices[$serviceId] = $definition->getClass();
        }
        $containerBuilder->setParameter('doubleServices', $doubledServices);
    }
}
