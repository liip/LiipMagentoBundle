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

    protected $app;

    public function __construct(StoreResolverInterface $resolver, $defaultStore)
    {
        $this->storeResolver = $resolver;
        $this->defaultStore = $defaultStore;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequestInitApp(GetResponseEvent $event)
    {
        if ($event->getRequestType() == HttpKernelInterface::MASTER_REQUEST) {

            $this->app = \Mage::app();
        }
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequestSetStore(GetResponseEvent $event)
    {
        if ($event->getRequestType() == HttpKernelInterface::MASTER_REQUEST
            && $this->app
        ) {
            $store = $this->storeResolver->resolve($event->getRequest());

            if (!$store) {
                $store = $this->defaultStore;
            }

            // run Magento
            $this->app->setCurrentStore($store);
        }
    }
}
