<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Menu;

use Knp\Menu\ItemInterface;
use Knp\Menu\MenuItem;
use T3G\Bundle\Keycloak\Security\KeyCloakUser;
use T3G\Bundle\TemplateBundle\Menu\MenuBuilder as TemplateMenuBuider;
use T3G\Bundle\TemplateBundle\Utility\AvatarUtility;

/**
 * MenuBuilder
 */
class MenuBuilder extends TemplateMenuBuider
{
    /**
     * @param array $options
     * @return ItemInterface|MenuItem
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
                    'icon' => 'actions-extension',
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
            $menu['core']->addChild($this->getDivider());
            $menu['core']->addChild(
                'bamboo_core_security',
                [
                    'route' => 'admin_bamboo_core_security',
                    'label' => 'Security Build Plans',
                    'extras' => [
                        'icon' => 'actions-shield',
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
                    'icon' => 'actions-notebook',
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
            $menu['documentation']->addChild($this->getDivider());
            $menu['documentation']->addChild(
                'docs_redirect_index',
                [
                    'route' => 'admin_redirect_index',
                    'label' => 'Redirects',
                ]
            );
            $menu['documentation']->addChild(
                'docs_blacklist_index',
                [
                    'route' => 'admin_docs_deployments_blacklist_index',
                    'label' => 'Blacklist',
                ]
            );
        }
        if ($this->authorizationChecker->isGranted('ROLE_USER')) {
            $menu['documentation']->addChild($this->getDivider());
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
                'discord_scheduled_messages',
                [
                    'route' => 'admin_discord_scheduled_messages',
                    'label' => 'Scheduled Messages',
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
        return $menu;
    }

    /**
     * @param array $options
     * @return ItemInterface|MenuItem
     */
    public function mainProfile(array $options)
    {
        $menu = $this->factory->createItem('root');
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
        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            /** @var KeyCloakUser $user */
            $user = $this->tokenStorage->getToken()->getUser();
            $menu->addChild(
                'username',
                [
                    'label' => $user->getDisplayName(),
                    'uri' => '#',
                    'extras' => [
                        'image' => AvatarUtility::getAvatarUrl($user->getEmail() ?? '', 32),
                    ],
                ]
            );
            $menu['username']->addChild(
                'logout',
                [
                    'label' => 'Sign out',
                    'route' => 'logout',
                    'extras' => [
                        'icon' => 'actions-logout',
                    ],
                ]
            );
        } else {
            $menu->addChild(
                'login',
                [
                    'route' => 'admin_login',
                    'label' => 'Login',
                    'extras' => [
                        'icon' => 'actions-login',
                    ],
                ]
            )->setLinkAttribute('class', 'btn btn-primary');
        }
        return $menu;
    }
}
