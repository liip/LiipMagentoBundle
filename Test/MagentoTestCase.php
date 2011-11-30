<?php

namespace Liip\MagentoBundle\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MagentoTestCase extends WebTestCase
{
    static protected function createClient(array $options = array(), array $server = array())
    {
        // let the parent create the client
        $client = parent::createClient($options, $server);

        // load the magefile, create the mage app and dispatch the         
        // symfony_on_kernel_request event so the mage-container is properly initialized
        require_once static::$kernel->getContainer()
                ->getParameter('liip_magento.mage_file');

        $params = array('container' => self::$kernel->getContainer());
        \Mage::app()->dispatchEvent('symfony_on_kernel_request', $params);
        return $client;

    }
}