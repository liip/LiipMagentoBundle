<?php

namespace Liip\MagentoBundle\StoreResolver;

use Symfony\Component\HttpFoundation\Request;

interface StoreResolverInterface
{
    /**
     * Resolves the Magento store code to be initiated
     *
     * @param   Request $request
     * @return  string|false    The resolved store code or false if no mapping found
     */
    function resolve(Request $request);
}

