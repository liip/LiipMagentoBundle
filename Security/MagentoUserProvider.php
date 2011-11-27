<?php

namespace Liip\MagentoBundle\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

class MagentoUserProvider implements UserProviderInterface
{
    protected $class;

    public function __construct($class)
    {
        $this->class = $class;
    }

    /**
     * @param $id int Magento user ID
     */
    public function loadUserByUsername($id)
    {
        $customer = \Mage::getModel('customer/customer')->load($id);

        if ($customer->getId()) {
            return new $this->class($customer->getId(), $customer->getEmail(), $customer->getFirstname(), $customer->getLastname(), $customer->getGroupId());
        }

        throw new UsernameNotFoundException(sprintf('User "%s" not found.', $email));
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof MagentoUserInterface) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getId());
    }

    public function supportsClass($class)
    {
        return $class === $this->class;
    }
}
