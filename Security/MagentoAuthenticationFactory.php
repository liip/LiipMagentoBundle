<?php

namespace Liip\MagentoBundle\Security;

use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FormLoginFactory;

class MagentoAuthenticationFactory extends FormLoginFactory
{
    public function getKey()
    {
        return 'magento';
    }

    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        $provider = 'security.authentication.provider.magento.'.$id;
        $container->setDefinition($provider, new DefinitionDecorator('security.authentication.provider.magento'))
            ->replaceArgument(0, new Reference($userProviderId))
            ->replaceArgument(2, $id);

        if ($container->hasDefinition('security.logout_listener.'.$id)) {
            $container->getDefinition('security.logout_listener.'.$id)
                ->addMethodCall('addHandler', array(new Reference($provider)));
        }

        return $provider;
    }

    protected function getListenerId()
    {
        return 'security.authentication.listener.magento';
    }
}
