<?php
namespace Liip\MagentoBundle\Loader;

use \Symfony\Bundle\TwigBundle\Loader\FilesystemLoader;

class MagentoLoader extends FilesystemLoader {
    
    
    protected function findTemplate($template)
    {
        
        try {            
            // symfony loader tries first
            return parent::findTemplate($template);
            
        } catch (\Twig_Error_Loader $e) {

            $logicalName = (string) $template;
            $mageDesignDir = \Mage::getBaseDir('design');
            $file = $mageDesignDir . DIRECTORY_SEPARATOR . $logicalName;
            
            if (file_exists($file)) {
                return $this->cache[$logicalName] = $file;                
            } else {                
                throw new \Twig_Error_Loader(sprintf('Unable to find magento template "%s".', $logicalName), -1, null, $e);
            }
        }        
    }   
}
