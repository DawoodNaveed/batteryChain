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

        /*
            NOTE: score conditional is optional, since the successful score default
            is set at >= 0.5 by Google. Some developers want to
            be able to control score result conditions, so added it
        */
        if ($resp->isSuccess() && $resp->getScore() >= 0.5) {
           return true;
        }

        return false;
    }
}