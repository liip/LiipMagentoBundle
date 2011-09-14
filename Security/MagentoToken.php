<?php

namespace Liip\MagentoBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\User\UserInterface;

class MagentoToken extends AbstractToken
{
    private $providerKey;

    public function __construct(UserInterface $user, $providerKey) {
        parent::__construct($user->getRoles());

        if (empty($providerKey)) {
            throw new \InvalidArgumentException('$providerKey must not be empty.');
        }

        $this->providerKey = $providerKey;

        $this->setUser($user);
        parent::setAuthenticated(true);
    }

    public function getProviderKey()
    {
        return $this->providerKey;
    }

    public function getCredentials()
    {
        return '';
    }

    public function serialize()
    {
        return serialize(array(
            $this->providerKey,
            parent::serialize(),
        ));
    }

    public function unserialize($serialized)
    {
        list($this->providerKey, $parentStr) = unserialize($serialized);
        parent::unserialize($parentStr);
    }
}
