<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\ApiBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Config\ConfigInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ContainerBuilder;
use Contao\ManagerPlugin\Config\ExtensionPluginInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use HeimrichHannot\ApiBundle\ContaoApiBundle;
use HeimrichHannot\UtilsBundle\Container\ContainerUtil;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class Plugin implements BundlePluginInterface, RoutingPluginInterface, ExtensionPluginInterface
{
    /**
     * Gets a list of autoload configurations for this bundle.
     *
     * @return ConfigInterface[]
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(ContaoApiBundle::class)->setLoadAfter(
                [
                    ContaoCoreBundle::class,
                ]
            ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel)
    {
        $file = '@ContaoApiBundle/Resources/config/routing.yml';

        return $resolver->resolve($file)->load($file);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensionConfig($extensionName, array $extensionConfigs, ContainerBuilder $container)
    {
        if ('security' === $extensionName) {
            $extensionConfigs = $this->getSecurityExtensionConfig($extensionConfigs, $container);

            return $extensionConfigs;
        }

        return ContainerUtil::mergeConfigFile(
            'huh_api',
            $extensionName,
            $extensionConfigs,
            __DIR__.'/../Resources/config/config.yml'
        );
    }

    /**
     * Get security extension config.
     *
     * @return array
     */
    public function getSecurityExtensionConfig(array $extensionConfigs, ContainerBuilder $container)
    {
        $firewalls = [
            'api_login_member' => [
                'request_matcher' => 'huh.api.routing.login.member.matcher',
                'stateless' => true,
                'guard' => [
                    'authenticators' => ['huh.api.security.username_password_authenticator'],
                ],
                'provider' => 'contao.security.frontend_user_provider',
            ],
            'api_login_user' => [
                'request_matcher' => 'huh.api.routing.login.user.matcher',
                'stateless' => true,
                'guard' => [
                    'authenticators' => ['huh.api.security.username_password_authenticator'],
                ],
                'provider' => 'contao.security.backend_user_provider',
            ],
            'api' => [
                'request_matcher' => 'huh.api.routing.matcher',
                'stateless' => true,
                'guard' => [
                    'authenticators' => ['huh.api.security.token_authenticator'],
                ],
                'provider' => 'huh.api.security.user_provider',
            ],
        ];

        $providers = [
            'huh.api.security.user_provider' => [
                'id' => 'huh.api.security.user_provider',
            ],
        ];

        foreach ($extensionConfigs as &$extensionConfig) {
            $extensionConfig['firewalls'] = (isset($extensionConfig['firewalls']) && \is_array($extensionConfig['firewalls']) ? $extensionConfig['firewalls'] : []) + $firewalls;
            $extensionConfig['providers'] = (isset($extensionConfig['providers']) && \is_array($extensionConfig['providers']) ? $extensionConfig['providers'] : []) + $providers;
        }

        return $extensionConfigs;
    }
}
