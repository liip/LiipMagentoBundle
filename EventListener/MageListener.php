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

    /** @var    \Mage_Core_Model_App */
    protected $app;

    public function __construct(StoreResolverInterface $resolver)
    {
        $this->storeResolver = $resolver;
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

            if (false !== $store) {

                // keep default store in case manual override fails
                $defaultStore = $this->app->getStore()->getCode();

                $this->app->setCurrentStore($store);

                try {
                    // try to load the store
                    $this->app->getStore();

                } catch (\Mage_Core_Model_Store_Exception $e) {
                    $this->app->setCurrentStore($defaultStore);
                }
            }
        }
    }
}
