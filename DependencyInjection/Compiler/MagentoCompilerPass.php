<?php

namespace Liip\MagentoBundle\DependencyInjection\Compiler;

use \Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use \Symfony\Component\DependencyInjection\ContainerBuilder;

class MagentoCompilerPass implements CompilerPassInterface 
{
	public function process(ContainerBuilder $container) 
	{
	    $container->setAlias('templating.locator', 'liip_magento.templating_locator');
	    $container->setAlias('templating.name_parser', 'liip_magento.templating_name_parser');
	}
}