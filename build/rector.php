<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Symfony\Set\SymfonySetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    // get parameters
    $parameters = $containerConfigurator->parameters();

    $parameters->set(
        Option::SYMFONY_CONTAINER_XML_PATH_PARAMETER,
        __DIR__ . '/var/cache/dev/App_KernelDevDebugContainer.xml'
    );

    // Define what rule sets will be applied
    $parameters->set(Option::SETS, [
        DoctrineSetList::DOCTRINE_COMMON_20,
        DoctrineSetList::DOCTRINE_25,
        DoctrineSetList::DOCTRINE_DBAL_210,
        DoctrineSetList::DOCTRINE_DBAL_211,
        DoctrineSetList::DOCTRINE_DBAL_30,
        SymfonySetList::SYMFONY_40,
        SymfonySetList::SYMFONY_41,
        SymfonySetList::SYMFONY_42,
        SymfonySetList::SYMFONY_43,
        SymfonySetList::SYMFONY_44,
        SymfonySetList::SYMFONY_50,
        SymfonySetList::SYMFONY_52,
        SymfonySetList::SYMFONY_50_TYPES,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
    ]);

    // get services (needed for register a single rule)
    // $services = $containerConfigurator->services();

    // register a single rule
    // $services->set(TypedPropertyRector::class);
    $parameters->set(
        Option::PATHS,
        [
            __DIR__ . '/src',
            __DIR__ . '/tests',
        ]
    );
};
