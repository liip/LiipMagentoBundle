<?php

namespace Liip\MagentoBundle\StoreResolver;

use Symfony\Component\HttpFoundation\Request;

interface StoreResolverInterface
{
    /**
     * Resolves the Magento store code to be initiated
     *
     * @return  \Symfony\Component\HttpFoundation\Request $request
     */
    function resolve(Request $request);
}

