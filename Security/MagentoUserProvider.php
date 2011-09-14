<?php

namespace Liip\MagentoBundle\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class MagentoUserProvider implements UserProviderInterface
{
    protected $class;

    public function __construct($class)
    {
        $this->class = $class;
    }

    /**
     * {@inheritdoc}
     */
    function loadUserByUsername($email)
    {
        $customer = \Mage::getModel('customer/customer')->setWebsiteId(1)->loadByEmail($email); // TODO configurable website ID

        if ($user->getId()) {
            return new $this->class($customer->getId(), $customer->getEmail(), $customer->getFirstname(), $customer->getLastname());
        }

        throw new UsernameNotFoundException(sprintf('User "%s" not found.', $email));
    }

    /**
     * {@inheritdoc}
     */
    function supportsClass($class)
    {
        return $class === $this->class;
    }
}
