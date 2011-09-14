<?php

namespace Liip\MagentoBundle\Security;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class MagentoAuthenticationProvider implements AuthenticationProviderInterface, LogoutHandlerInterface
{
    protected $userProvider;
    protected $userChecker;
    protected $providerKey;

    public function __construct(UserProviderInterface $userProvider, UserCheckerInterface $userChecker, $providerKey)
    {
        if (empty($providerKey)) {
            throw new \InvalidArgumentException('$providerKey must not be empty.');
        }

        $this->userProvider = $userProvider;
        $this->userChecker = $userChecker;
        $this->providerKey = $providerKey;
    }

    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            return null;
        }

        return $token;
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof MagentoToken && $this->providerKey === $token->getProviderKey();
    }

    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        // Logout Magento session
        \Mage::getSingleton('customer/session')->logout();
    }
}
