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
        
        try {
            if (!$presentedPassword = $token->getCredentials()) {
                throw new BadCredentialsException('The presented password cannot be empty.');
            }

            if (\Mage::getSingleton('customer/session')->login($token->getUsername(), $token->getCredentials())) {

                $id = \Mage::getSingleton('customer/session')->getCustomerId();

                $user = $this->userProvider->loadUserByUsername($id);

                if (!$user instanceof UserInterface) {
                    throw new AuthenticationServiceException('The user provider must return an UserInterface object.');
                }

                $authenticatedToken = new MagentoToken($user, $this->providerKey);
                $authenticatedToken->setAttributes($token->getAttributes());
                return $authenticatedToken;
            }

            // login failed
            throw new BadCredentialsException('Bad credentials');

        } catch (\Exception $repositoryProblem) {

            throw new AuthenticationServiceException($repositoryProblem->getMessage(), $token, 0, $repositoryProblem);
        }
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof UsernamePasswordToken && $this->providerKey === $token->getProviderKey();
    }

    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        // Logout Magento session
        \Mage::getSingleton('customer/session')->logout();
    }
}
