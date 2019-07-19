<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\Matcher\MatcherInterface;
use Knp\Menu\MenuFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * MenuBuilder
 */
class MenuBuilder
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var MenuFactory
     */
    private $factory;

    /**
     * @var MatcherInterface
     */
    private $matcher;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param ContainerInterface $container
     * @param FactoryInterface $factory
     * @param MatcherInterface $matcher
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface $tokenStorage
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ContainerInterface $container,
        FactoryInterface $factory,
        MatcherInterface $matcher,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        TranslatorInterface $translator
    ) {
        $this->container = $container;
        $this->factory = $factory;
        $this->matcher = $matcher;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
    }

    /**
     * @param array $options
     */
    public function mainDefault(array $options)
    {
        $menu = $this->factory->createItem('root');

        $menu->addChild(
            'core',
            [
                'route' => 'admin_bamboo_core',
                'label' => 'Core',
                'extras' => [
                    'icon' => 'box',
                ],
            ]
        );
        $menu['core']->addChild(
            'bamboo_core',
            [
                'route' => 'admin_bamboo_core',
                'label' => 'Build Plans',
            ]
        );
        $menu['core']->addChild(
            'split_core',
            [
                'route' => 'admin_split_core',
                'label' => 'Split Core',
            ]
        );
        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            $menu['core']->addChild(
                'bamboo_core_security',
                [
                    'route' => 'admin_bamboo_core_security',
                    'label' => 'Security Build Plans',
                    'extras' => [
                        'icon' => 'lock',
                    ],
                ]
            );
        }
        $menu->addChild(
            'documentation',
            [
                'route' => 'admin_docs_deployments',
                'label' => 'Documentation',
                'extras' => [
                    'icon' => 'book',
                ],
            ]
        );
        $menu['documentation']->addChild(
            'docs_deployments',
            [
                'route' => 'admin_docs_deployments',
                'label' => 'Deployments',
            ]
        );
        if ($this->authorizationChecker->isGranted('ROLE_DOCUMENTATION_MAINTAINER')) {
            $menu['documentation']->addChild(
                'docs_redirect_index',
                [
                    'route' => 'admin_redirect_index',
                    'label' => 'Redirects',
                ]
            );
        }
        if ($this->authorizationChecker->isGranted('ROLE_USER')) {
            $menu['documentation']->addChild(
                'docs_index',
                [
                    'route' => 'admin_docs_third_party',
                    'label' => 'Third Party',
                ]
            );
        }
        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            $menu->addChild(
                'discord',
                [
                    'route' => 'admin_discord_webhooks',
                    'label' => 'Discord',
                    'extras' => [
                        'icon' => 'discord',
                        'fab' => true,
                    ],
                ]
            );
            $menu['discord']->addChild(
                'discord_webhooks',
                [
                    'route' => 'admin_discord_webhooks',
                    'label' => 'Webhooks',
                ]
            );
            $menu['discord']->addChild(
                'discord_howto',
                [
                    'route' => 'admin_discord_webhooks_howto',
                    'label' => 'Configuring Services',
                ]
            );
        }
        $menu->addChild(
            'help',
            [
                'route' => 'admin_index',
                'label' => 'Help',
                'extras' => [
                    'icon' => 'question-circle',
                ],
            ]
        );

        return $menu;
    }

    /**
     * @param array $options
     */
    public function mainProfile(array $options)
    {
        $menu = $this->factory->createItem('root');
        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $menu->addChild(
                'username',
                [
                    'label' => $this->tokenStorage->getToken()->getUsername(),
                    'uri' => '#',
                    'extras' => [
                        'icon' => 'user',
                    ],
                ]
            );
            $menu->addChild(
                'logout',
                [
                    'route' => 'logout',
                    'label' => 'Logout',
                    'extras' => [
                        'icon' => 'lock',
                    ],
                ]
            )->setLinkAttribute('class', 'btn btn-primary');
        } else {
            $menu->addChild(
                'login',
                [
                    'route' => 'admin_login',
                    'label' => 'Login',
                    'extras' => [
                        'icon' => 'unlock',
                    ],
                ]
            )->setLinkAttribute('class', 'btn btn-primary');
        }
        return $menu;
    }

    /**
     * @param array $options
     */
    public function mainFooter(array $options)
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild(
            'privacy',
            [
                'label' => 'Privacy Policy',
                'route' => 'admin_page_privacy',
            ]
        );
        $menu->addChild(
            'legal',
            [
                'label' => 'Legal Information',
                'route' => 'admin_page_legal',
            ]
        );
        return $menu;
    }
}
