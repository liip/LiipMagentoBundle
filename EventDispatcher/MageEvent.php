<?php

namespace Liip\MagentoBundle\EventDispatcher;

use \Symfony\Component\EventDispatcher\Event;

class MageEvent extends Event
{
    protected $mageEvent;
    
    public function __construct(\Varien_Event $mageEvent) 
    {
        $this->mageEvent = $mageEvent;        
    }
    
    public function getData($key='', $index=null, $default = null)
    {
        
        if ($data = $this->mageEvent->getData($key, $index)) {
            return $data;
        }
        
        return $default;
        
    } 
}