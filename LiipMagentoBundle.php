<?php

namespace Liip\MagentoBundle;

use Liip\MagentoBundle\DependencyInjection\Compiler\MagentoCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class LiipMagentoBundle extends Bundle
{   
   public function build(ContainerBuilder $container) 
   {
       parent::build($container);              
       $container->addCompilerPass(new MagentoCompilerPass());

   }
}