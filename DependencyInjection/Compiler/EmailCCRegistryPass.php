<?php

namespace Sygefor\Bundle\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EmailCCRegistryPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('sygefor_core.registry.email_cc')) {
            return;
        }

        $resolvers = array();
        $definition = $container->getDefinition('sygefor_core.registry.email_cc');
        foreach ($container->findTaggedServiceIds('sygefor_core.email_resolver') as $serviceId => $tag) {
            $def = $container->getDefinition($serviceId);
            $class = $def->getClass();
            $resolvers[] = $class;
        }
        $definition->replaceArgument(0, $resolvers);
    }
}
