<?php

namespace Liip\MagentoBundle\EventListener;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Liip\MagentoBundle\StoreResolver\StoreResolverInterface;

class MageListener
{
    /** @var StoreResolverInterface */
    protected $storeResolver;

    /** @var string     Default store code (or id) */
    protected $defaultStore;

    public function __construct(StoreResolverInterface $resolver, $defaultStore)
    {
        $this->storeResolver = $resolver;
        $this->defaultStore = $defaultStore;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->getRequestType() == HttpKernelInterface::MASTER_REQUEST) {

            $store = $this->storeResolver->resolve($event->getRequest());

            if (!$store) {
                $store = $this->defaultStore;
            }
             
            // run Magento
            \Mage::app()->setCurrentStore($store);
        }
    }
}
