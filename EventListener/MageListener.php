<?php

namespace Liip\MagentoBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Liip\MagentoBundle\StoreResolver\StoreResolverInterface;

class MageListener
{
    /** @var StoreResolverInterface */
    protected $storeResolver;
    
    /** @var ContainerInterface */
    protected $container;

    /** @var    \Mage_Core_Model_App */
    protected $app;

    public function __construct(ContainerInterface $container)
    {        
        $this->container = $container;
        $this->storeResolver = $container->get('liip_magento.store_resolver');
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequestInitApp(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            
            $this->app = \Mage::app();
            // pass the ContainerInterface to magento
            // see https://github.com/pulse00/Magento-Symfony-Module for an example usage
            \Mage::dispatchEvent('symfony_on_kernel_request', array('container' => $this->container));
            
        }
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequestSetStore(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()
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

    /**
     * @param EventInterface $event
     */
     public function onKernelResponse(FilterResponseEvent $event)
     {
         $event->getRequest()->getSession()->save();
     }
}
