<?php

namespace Liip\MagentoBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Response;

class MageListener
{
    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        // run Magento
        \Mage::app()->setCurrentStore(\Mage_Core_Model_App::ADMIN_STORE_ID);
    }
}
