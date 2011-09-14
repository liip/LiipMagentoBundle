<?php

namespace Liip\MagentoBundle\Security;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

use Symfony\Component\Security\Http\Firewall\ListenerInterface;

use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\RememberMe\RememberMeServicesInterface;
use Symfony\Component\Security\Http\Firewall\UsernamePasswordFormAuthenticationListener;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MagentoAuthenticationListener implements ListenerInterface
{
    protected $options;
    protected $logger;
    protected $authenticationManager;
    protected $providerKey;
    protected $httpUtils;

    private $securityContext;
    private $sessionStrategy;
    private $dispatcher;
    private $successHandler;
    private $failureHandler;
    private $rememberMeServices;

    private $userProvider;
    private $csrfProvider;

    public function __construct(SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager, SessionAuthenticationStrategyInterface $sessionStrategy, HttpUtils $httpUtils, $providerKey, array $options = array(), AuthenticationSuccessHandlerInterface $successHandler = null, AuthenticationFailureHandlerInterface $failureHandler = null, LoggerInterface $logger = null, EventDispatcherInterface $dispatcher = null, $userProvider = null, CsrfProviderInterface $csrfProvider = null)
    {
        if (empty($providerKey)) {
            throw new \InvalidArgumentException('$providerKey must not be empty.');
        }

        $this->securityContext = $securityContext;
        $this->authenticationManager = $authenticationManager;
        $this->sessionStrategy = $sessionStrategy;
        $this->providerKey = $providerKey;
        $this->successHandler = $successHandler;
        $this->failureHandler = $failureHandler;
        $this->options = array_merge(array(
            'check_path'                     => '/login_check',
            'login_path'                     => '/login',
            'always_use_default_target_path' => false,
            'default_target_path'            => '/',
            'target_path_parameter'          => '_target_path',
            'use_referer'                    => false,
            'failure_path'                   => null,
            'failure_forward'                => false,
            'username_parameter'             => '_username',
            'password_parameter'             => '_password',
            'csrf_parameter'                 => '_csrf_token',
            'intention'                      => 'authenticate',
            'post_only'                      => true,
        ), $options);
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
        $this->httpUtils = $httpUtils;

        $this->userProvider = $userProvider;
        $this->csrfProvider = $csrfProvider;
    }

    /**
     * Handles form based authentication.
     *
     * @param GetResponseEvent $event A GetResponseEvent instance
     */
    public function handle(GetResponseEvent $event)
    {
        if (null !== $this->securityContext->getToken()) {
            return;
        }

        $request = $event->getRequest();

        if (!\Mage::getSingleton('customer/session')->isLoggedIn()) {
            return;
        }

        if (null !== $this->logger) {
            $this->logger->debug('Remember-me cookie detected.');
        }

        try {
            $id = \Mage::getSingleton('customer/session')->getCustomerId();

            $user = $this->userProvider->loadUserByUsername($id);

            if (null !== $this->logger) {
                $this->logger->info('Remember-me cookie accepted.');
            }

            $token = new MagentoToken($user, $this->providerKey);
            $this->securityContext->setToken($token);

            if (null !== $this->dispatcher) {
                $loginEvent = new InteractiveLoginEvent($request, $token);
                $this->dispatcher->dispatch(SecurityEvents::INTERACTIVE_LOGIN, $loginEvent);
            }

            if (null !== $this->logger) {
                $this->logger->debug('SecurityContext populated with remember-me token.');
            }
        } catch (AuthenticationException $failed) {
            if (null !== $this->logger) {
                $this->logger->warn(
                    'SecurityContext not populated with remember-me token as the'
                .' AuthenticationManager rejected the AuthenticationToken returned'
                .' by the RememberMeServices: '.$failed->getMessage()
                );
            }
        } catch (UsernameNotFoundException $notFound) {
            if (null !== $this->logger) {
                $this->logger->info('Magento user for not found.');
            }
        } catch (UnsupportedUserException $unSupported) {
            if (null !== $this->logger) {
                $this->logger->warn('User class not supported.');
            }
        } catch (AuthenticationException $invalid) {
            if (null !== $this->logger) {
                $this->logger->debug('Magento authentication failed: '.$invalid->getMessage());
            }
        }
    }
}