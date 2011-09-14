<?php

namespace Liip\MagentoBundle\StoreResolver;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Maps symfony's locale 1:1 to magento store code, no mapping configuration required
 */
class LocaleStore implements StoreResolverInterface
{
    /**
     * @param   array   $storeMappings
     */
    public function __construct($storeMappings)
    {
        // NOP
    }

    /**
     * @param   Request $request
     * @return  string|false    The resolved store code or false if no mapping found
     */
    public function resolve(Request $request)
    {
        return $request->getSession()->getLocale();
    }
}


