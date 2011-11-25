<?php
namespace Liip\MagentoBundle\Templating\Locator;

use Liip\MagentoBundle\Templating\MagentoTemplateReference;

use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Templating\TemplateReferenceInterface;

use \Symfony\Bundle\FrameworkBundle\Templating\Loader\TemplateLocator as BaseTemplateLocator;

class TemplateLocator extends BaseTemplateLocator {
    
    public function locate($template, $currentPath = null, $first = true)
    {
                 
        if ($template instanceof MagentoTemplateReference) {
            
            $logicalName = (string) $template;
            $mageDesignDir = \Mage::getBaseDir('design');
            $file = $mageDesignDir . DIRECTORY_SEPARATOR . $logicalName;
            
            if (file_exists($file)) {
                return $this->cache[$logicalName] = $file;
            } else {
                throw new \Twig_Error_Loader(sprintf('Unable to find magento template "%s".', $logicalName), -1, null, $e);
            }
        }
        
        return parent::locate($template, $currentPath, $first);
            
    }
}
