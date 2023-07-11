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
use T3G\Bundle\Keycloak\Security\KeyCloakUser;
use T3G\Bundle\TemplateBundle\Menu\MenuBuilder as TemplateMenuBuider;
use T3G\Bundle\TemplateBundle\Utility\AvatarUtility;

/**
 * MenuBuilder.
 */
class MenuBuilder extends TemplateMenuBuider
{
    public function mainDefault(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root');

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

        return $menu;
    }

    public function mainProfile(array $options): ItemInterface
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
