<?php

namespace Liip\MagentoBundle\StoreResolver;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Resolves the magento store code by looking at symfony's locale
 * and the configured mapping
 */
class LocaleStoreResolver implements StoreResolverInterface
{
    /** @var array Locale to Magento store code mapping*/
    protected $storeMappings;

    /**
     * @param   array   $storeMappings
     */
    public function __construct($storeMappings)
    {
        $this->storeMappings = $storeMappings;
    }

    /**
     * @param   Request $request
     * @return  string|false    The resolved store code or false if no mapping found
     */
    public function resolve(Request $request)
    {
        $store = false;

        $locale = $request->getSession()->getLocale();
        if (array_key_exists($locale, $this->storeMappings)) {
            $store = $this->storeMappings[$locale];
        }

        return $store;
    }
}

