<?php

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
    Symfony\WebpackEncoreBundle\WebpackEncoreBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class => ['dev' => true, 'test' => true],
    T3G\LibTestHelper\TestDoubleBundle::class => ['test' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    Symfony\Bundle\MakerBundle\MakerBundle::class => ['dev' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle::class => ['dev' => true, 'test' => true],
    Knp\Bundle\MenuBundle\KnpMenuBundle::class => ['all' => true],
    Knp\Bundle\PaginatorBundle\KnpPaginatorBundle::class => ['all' => true],
    T3G\Bundle\TemplateBundle\TemplateBundle::class => ['all' => true],
    T3G\Bundle\UsercentricsBundle\UsercentricsBundle::class => ['all' => true],
    Http\HttplugBundle\HttplugBundle::class => ['all' => true],
    Sentry\SentryBundle\SentryBundle::class => ['prod' => true],
    KnpU\OAuth2ClientBundle\KnpUOAuth2ClientBundle::class => ['all' => true],
    T3G\Bundle\Datahub\T3GDatahubBundle::class => ['all' => true],
    T3G\Bundle\Keycloak\T3GKeycloakBundle::class => ['all' => true],
];
