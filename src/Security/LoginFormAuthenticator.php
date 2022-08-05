<?php

namespace App\Security;

use App\Repository\UserRepository;
use App\Service\ReCaptchaService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;


/**
 * Class LoginFormAuthenticator
 * @property UrlGeneratorInterface urlGenerator
 * @property UserRepository userRepository
 * @property RequestStack requestStack
 * @property LoggerInterface logger
 * @property ReCaptchaService reCaptchaService
 */
class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    /**
     * LoginFormAuthenticator constructor.
     * @param UrlGeneratorInterface $urlGenerator
     * @param UserRepository $userRepository
     * @param RequestStack $requestStack
     * @param LoggerInterface $logger
     * @param ReCaptchaService $reCaptchaService
     */
    public function __construct(UrlGeneratorInterface $urlGenerator, UserRepository $userRepository, RequestStack $requestStack, LoggerInterface $logger, ReCaptchaService $reCaptchaService)
    {
        $this->urlGenerator = $urlGenerator;
        $this->userRepository = $userRepository;
        $this->requestStack = $requestStack;
        $this->logger = $logger;
        $this->reCaptchaService = $reCaptchaService;
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function supports(Request $request): bool
    {
        return $request->isMethod('POST') && $this->getLoginUrl($request) === $request->getRequestUri();
    }

    /**
     * @param Request $request
     * @return Passport|Response
     */
    public function authenticate(Request $request)
    {
        $username = $request->request->get('_username', '');
        $request->getSession()->set(Security::LAST_USERNAME, $username);

        if ($this->reCaptchaService->validateReCaptcha($request)) {
            return new Passport(
                new UserBadge($username),
                new PasswordCredentials($request->request->get('_password', '')),
                [
                    new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                    new RememberMeBadge(),
                ]
            );
        }

        $GLOBALS['validReCaptcha'] = false;

        return new Passport(new UserBadge(''),
            new PasswordCredentials('')
        );
    }

    /**
     * @param Request $request
     * @param TokenInterface $token
     * @param string $firewallName
     * @return Response|null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);

        if ($targetPath) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('home'));
    }

    /**
     * @param Request $request
     * @param AuthenticationException $exception
     * @return Response
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($request->hasSession()) {
            if (isset($GLOBALS['validReCaptcha']) && $GLOBALS['validReCaptcha'] === false) {
                $exception = new AuthenticationException("The reCAPTCHA wasn't entered correctly.");
            } else {
                $exception = new AuthenticationException('Invalid username or password');
            }
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception->getMessage());
        }

        $url = $this->getLoginUrl($request);

        return new RedirectResponse($url);
    }

    /**
     * @param Request $request
     * @return string
     */
    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}