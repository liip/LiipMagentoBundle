<?php
namespace Liip\MagentoBundle\Templating;
use Liip\MagentoBundle\Templating\MagentoTemplateReference;

use \Symfony\Bundle\FrameworkBundle\Templating\TemplateNameParser as BaseTemplateNameParser;

class TemplateNameParser extends BaseTemplateNameParser {
    
    public function parse($name) {
        
        try {
            
            return parent::parse($name);
            
        } catch (\InvalidArgumentException $e) {
            
            return $this->parseMagentoName($name);
        }
                
        throw new \InvalidArgumentException("Unable to parse magento template " . $name);
        
    }
    
    protected function parseMagentoName($name) {
                
        $separator = DIRECTORY_SEPARATOR;        
        $parts = explode($separator, $name);
        
        if(count($parts) < 4 || $parts[3] !== 'template') {
            throw new \InvalidArgumentException("Unable to parse magento template " . $name);
        }
        
        return new MagentoTemplateReference($name);
        
    }
}
