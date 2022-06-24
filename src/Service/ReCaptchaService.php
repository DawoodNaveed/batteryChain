<?php

namespace App\Service;

use ReCaptcha\ReCaptcha;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ReCaptchaService
 * @package App\Service
 * @property $recaptchaSecretKey
 */
class ReCaptchaService
{
    public function __construct($recaptchaSecretKey)
    {
        $this->recaptchaSecretKey = $recaptchaSecretKey;
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function validateReCaptcha(Request $request): bool
    {
        $recaptcha = new ReCaptcha($this->recaptchaSecretKey);
        $resp = $recaptcha->verify(
            $request->request->get('g-recaptcha-response'),
            $request->getClientIp()
        );

        if ($resp->isSuccess()) {
           return true;
        }

        return false;
    }
}