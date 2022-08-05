<?php

namespace App\Listener;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class LocaleListener
 * @package App\Listener
 * @property TranslatorInterface translator
 * @property UrlGeneratorInterface urlGenerator
 * @property SessionInterface session
 * @property $availableLocales
 */
class LocaleListener implements EventSubscriberInterface
{
    /**
     * LocaleListener constructor.
     * @param TranslatorInterface $translator
     * @param UrlGeneratorInterface $urlGenerator
     * @param SessionInterface $session
     * @param $availableLocales
     */
    public function __construct(
        TranslatorInterface $translator,
        UrlGeneratorInterface $urlGenerator,
        SessionInterface $session,
        $availableLocales
    ) {
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
        $this->session = $session;
        $this->availableLocales = $availableLocales;
    }

    /**
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        $sessionSet = false;

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
            $this->session->set('_locale', $request->get('_locale'));
            $sessionSet = true;
        }

        if (!empty($this->session->get('_locale'))) {
            $request->setLocale($this->session->get('_locale'));
            $this->translator->setLocale($this->session->get('_locale'));
            $sessionSet = true;
        }

        if ($sessionSet === false) {
            $lang = substr($request->server->get('HTTP_ACCEPT_LANGUAGE'), 0, 2);
            $acceptLang = explode('|', $this->availableLocales);
            $lang = in_array($lang, $acceptLang) ? $lang : 'en';
            $request->setLocale($lang);
            $this->translator->setLocale($lang);
            $this->session->set('_locale', $lang);
        }
    }

    /**
     * @return \array[][]
     */
    static public function getSubscribedEvents()
    {
        return array(
            // must be registered before the default Locale listener
            KernelEvents::REQUEST => array(array('onKernelRequest', 17)),
        );
    }
}