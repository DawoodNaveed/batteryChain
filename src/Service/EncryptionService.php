<?php

namespace App\Service;

/**
 * Class EncryptionService
 * @package App\Service
 * @property $openssl_cipher
 * @property $openssl_iv
 * @property $openssl_password
 */
class EncryptionService
{
    const BATTERY_ENCRYPTION = '|battery';
    const CIPHER_ENCRYPTION = 'ASCII';

    /**
     * EncryptionService constructor.
     * @param $openssl_cipher
     * @param $openssl_iv
     * @param $openssl_password
     */
    public function __construct($openssl_cipher, $openssl_iv, $openssl_password)
    {
        $this->openssl_cipher = $openssl_cipher;
        $this->openssl_iv = $openssl_iv;
        $this->openssl_password = $openssl_password;
    }

    /**
     * Using Base 64 encode to prevent "encrypted text" having forward slashes '/'
     * @param string $string
     * @return false|string
     */
    public function encryptString(string $string)
    {
        return base64_encode(openssl_encrypt(
            $string . self::BATTERY_ENCRYPTION,
            $this->openssl_cipher,
            $this->openssl_password,
            0,
            $this->openssl_iv
        ));
    }

    /**
     * @param string|null $string $string
     * @return false|string
     */
    public function decryptString(?string $string)
    {
        return openssl_decrypt(
            base64_decode($string),
            $this->openssl_cipher,
            $this->openssl_password,
            0,
            $this->openssl_iv
        );
    }

    /**
     * @param string $decryptedNumber
     * @return false|string
     */
    public function validateAndFetchSerialNumber(string $decryptedNumber)
    {
        if (mb_detect_encoding($decryptedNumber) === self::CIPHER_ENCRYPTION && str_contains($decryptedNumber, self::BATTERY_ENCRYPTION)) {
            $decryptedNumber = explode('|', $decryptedNumber);
            return (string) $decryptedNumber[0];
        }

        return false;
    }
}