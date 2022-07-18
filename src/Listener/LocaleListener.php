<?php

namespace App\Listener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LocaleListener implements EventSubscriberInterface
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var RouterInterface */
    private $router;

    /**
     * LocaleListener constructor.
     * @param TranslatorInterface $translator
     * @param UrlGeneratorInterface $urlGenerator
     * @param RouterInterface $router
     */
    public function __construct(
        TranslatorInterface $translator,
        UrlGeneratorInterface $urlGenerator,
        RouterInterface $router
    ) {
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
        $this->router = $router;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        if (!$request->hasPreviousSession()) {
            return;
        }

        if (!$event->isMainRequest()) {
            // don't do anything if it's not the main request
            return;
        }

        if (strlen($request->get('_locale') )) {
            $request->setLocale($request->get('_locale'));
            $this->translator->setLocale($request->get('_locale'));
        }

        // try to see if the locale has been set as a _locale routing parameter
        if ($locale = $request->query->get('switch_language')) {

            $request->getSession()->set('_locale', $locale);
            $routing = $this->router->match($request->getPathInfo());

            $route_params = array();

            foreach ($routing as $key => $value) {
                if($key[0] !== "_")
                {
                    $route_params[$key] = $value;
                }
            }

            $parameters = \array_merge($route_params, array("_locale" => $locale));
            $url = $this->urlGenerator->generate($routing['_route'], $parameters);

            $response = new RedirectResponse($url);
            $event->setResponse($response);
        }
    }

    static public function getSubscribedEvents()
    {
        return array(
            // must be registered before the default Locale listener
            KernelEvents::REQUEST => array(array('onKernelRequest', 17)),
        );
    }
}