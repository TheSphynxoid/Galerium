<?php

namespace App\Service;

class NavigationService
{
    public static function getNavigationItems(): array
    {
        return [
            [
                'label' => 'Home',
                'route' => 'app_home',
                'roles' => ['PUBLIC'],
            ],
            [
                'label' => 'Login',
                'route' => 'app_login',
                'roles' => ['PUBLIC'],
            ],
            [
                'label' => 'Register',
                'route' => 'app_registre',
                'roles' => ['PUBLIC'],
            ],
            [
                'label' => 'Login with Google',
                'route' => 'connect_google',
                'roles' => ['PUBLIC'],
            ],
            
            [
                'label' => 'Admin Dashboard',
                'route' => 'app_admin',
                'roles' => ['ADMIN'],
            ],
            [
                'label' => 'Users Management',
                'route' => 'app_utilisateur_index',
                'roles' => ['ADMIN'],
            ],
            
            [
                'label' => 'Artiste Dashboard',
                'route' => 'app_artiste',
                'roles' => ['ADMIN','ARTISTE'],
            ],
            
            [
                'label' => 'Jury Dashboard',
                'route' => 'app_jury',
                'roles' => ['ADMIN','JURY'],
            ],
            
            [
                'label' => 'Visiteur Dashboard',
                'route' => 'app_visiteur',
                'roles' => ['ADMIN','VISITEUR'],
            ],
            
            [
                'label' => 'Profile',
                'route' => 'app_me',
                'roles' => ['ADMIN', 'ARTISTE', 'JURY', 'VISITEUR'],
            ],
            [
                'label' => 'Logout',
                'route' => 'app_logout',
                'roles' => ['ADMIN', 'ARTISTE', 'JURY', 'VISITEUR'],
            ],
        ];
    }

    public static function getFilteredItems(?string $userRole = null): array
    {
        $allItems = self::getNavigationItems();
        $filtered = [];

        foreach ($allItems as $item) {
            if (!$userRole && in_array('PUBLIC', $item['roles'])) {
                $filtered[] = $item;
            }
            elseif ($userRole && (
                in_array('PUBLIC', $item['roles']) || 
                in_array($userRole, $item['roles'])
            )) {
                if ($userRole && in_array($userRole, ['ADMIN', 'ARTISTE', 'JURY', 'VISITEUR'])) {
                    if (!in_array($item['route'], ['app_login', 'app_registre', 'connect_google'])) {
                        $filtered[] = $item;
                    }
                } else {
                    $filtered[] = $item;
                }
            }
        }

        return $filtered;
    }
}

