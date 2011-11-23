<?php

namespace Liip\MagentoBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
use Symfony\Component\Security\Core\Exception\SessionUnavailableException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
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
        if ($this->requiresAuthentication($request)) {

            if (!$request->hasSession()) {
                throw new \RuntimeException('This authentication method requires a session.');
            }

            try {
                if (!$request->hasPreviousSession()) {
                    throw new SessionUnavailableException('Your session has timed-out, or you have disabled cookies.');
                }

                if (null === $returnValue = $this->attemptAuthentication($request)) {
                    return;
                }

                if ($returnValue instanceof TokenInterface) {

                    $this->sessionStrategy->onAuthentication($request, $returnValue);

                    $response = $this->onSuccess($event, $request, $returnValue);

                } else if ($returnValue instanceof Response) {
                    $response = $returnValue;
                } else {
                    throw new \RuntimeException('attemptAuthentication() must either return a Response, an implementation of TokenInterface, or null.');
                }
            } catch (AuthenticationException $e) {
                $response = $this->onFailure($event, $request, $e);
            }

            $event->setResponse($response);

        } else {

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

    /**
     * Whether this request requires authentication.
     *
     * The default implementation only processed requests to a specific path,
     * but a subclass could change this to only authenticate requests where a
     * certain parameters is present.
     *
     * @param Request $request
     *
     * @return Boolean
     */
    protected function requiresAuthentication(Request $request)
    {
        return $this->httpUtils->checkRequestPath($request, $this->options['check_path']);
    }

    protected function attemptAuthentication(Request $request)
    {
        if ($this->options['post_only'] && 'post' !== strtolower($request->getMethod())) {
            if (null !== $this->logger) {
                $this->logger->debug(sprintf('Authentication method not supported: %s.', $request->getMethod()));
            }

            return null;
        }

        if (null !== $this->csrfProvider) {
            $csrfToken = $request->get($this->options['csrf_parameter'], null, true);

            if (false === $this->csrfProvider->isCsrfTokenValid($this->options['intention'], $csrfToken)) {
                throw new InvalidCsrfTokenException('Invalid CSRF token.');
            }
        }

        $username = trim($request->get($this->options['username_parameter'], null, true));
        $password = $request->get($this->options['password_parameter'], null, true);

        $request->getSession()->set(SecurityContextInterface::LAST_USERNAME, $username);

        return $this->authenticationManager->authenticate(new UsernamePasswordToken($username, $password, $this->providerKey));
    }

    private function onFailure(GetResponseEvent $event, Request $request, AuthenticationException $failed)
    {
        if (null !== $this->logger) {
            $this->logger->info(sprintf('Authentication request failed: %s', $failed->getMessage()));
        }

        $this->securityContext->setToken(null);

        if (null !== $this->failureHandler) {
            return $this->failureHandler->onAuthenticationFailure($request, $failed);
        }

        if (null === $this->options['failure_path']) {
            $this->options['failure_path'] = $this->options['login_path'];
        }

        if ($this->options['failure_forward']) {
            if (null !== $this->logger) {
                $this->logger->debug(sprintf('Forwarding to %s', $this->options['failure_path']));
            }

            $subRequest = $this->httpUtils->createRequest($request, $this->options['failure_path']);
            $subRequest->attributes->set(SecurityContextInterface::AUTHENTICATION_ERROR, $failed);

            return $event->getKernel()->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        }

        if (null !== $this->logger) {
            $this->logger->debug(sprintf('Redirecting to %s', $this->options['failure_path']));
        }

        $request->getSession()->set(SecurityContextInterface::AUTHENTICATION_ERROR, $failed);

        return $this->httpUtils->createRedirectResponse($request, $this->options['failure_path']);
    }

    private function onSuccess(GetResponseEvent $event, Request $request, TokenInterface $token)
    {
        if (null !== $this->logger) {
            $this->logger->info(sprintf('User "%s" has been authenticated successfully', $token->getUsername()));
        }

        $this->securityContext->setToken($token);

        $session = $request->getSession();
        $session->remove(SecurityContextInterface::AUTHENTICATION_ERROR);
        $session->remove(SecurityContextInterface::LAST_USERNAME);

        if (null !== $this->dispatcher) {
            $loginEvent = new InteractiveLoginEvent($request, $token);
            $this->dispatcher->dispatch(SecurityEvents::INTERACTIVE_LOGIN, $loginEvent);
        }

        if (null !== $this->successHandler) {
            $response = $this->successHandler->onAuthenticationSuccess($request, $token);
        } else {
            $path = str_replace('{_locale}', $session->getLocale(), $this->determineTargetUrl($request));
            $response = new RedirectResponse(0 !== strpos($path, 'http') ? $request->getUriForPath($path) : $path, 302);
        }

        return $response;
    }

    /**
     * Builds the target URL according to the defined options.
     *
     * @param Request $request
     *
     * @return string
     */
    private function determineTargetUrl(Request $request)
    {
        if ($this->options['always_use_default_target_path']) {
            return $this->options['default_target_path'];
        }

        if ($targetUrl = $request->get($this->options['target_path_parameter'], null, true)) {
            return $targetUrl;
        }

        $session = $request->getSession();
        if ($targetUrl = $session->get('_security.target_path')) {
            $session->remove('_security.target_path');

            return $targetUrl;
        }

        if ($this->options['use_referer'] && $targetUrl = $request->headers->get('Referer')) {
            return $targetUrl;
        }

        return $this->options['default_target_path'];
    }
}