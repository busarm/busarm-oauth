<?php

namespace System;

use Throwable;

/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 12/31/2017
 * Time: 12:51 PM
 */


class Encrypter
{
    const METHOD = "AES-256-CBC";
    const KEY_HASH_ALGO = "md5";
    const KEY_HASH_INTERATIONS = 16;
    const KEY_HASH_LENGTH = 64;
    const KEY_SALT_LENGTH = 16;
    const KEY_IV_LENGTH = 16;
    const HMAC_HASH_ALGO = "sha1";

    /**
     * Encrypter Data for client
     * @param string $paraphrase  Encryption Key
     * @param string $plain Data to encrypt
     * @param string $iv
     * @return mixed Base64 encoded result of encrypted data
     */
    public static function encrypt($passphrase, $plain)
    {
        if (!empty($passphrase)) {
            $salt = openssl_random_pseudo_bytes(self::KEY_SALT_LENGTH);
            $iv = openssl_random_pseudo_bytes(self::KEY_IV_LENGTH);
            $key = hash_pbkdf2(self::KEY_HASH_ALGO, $passphrase, $salt, self::KEY_HASH_INTERATIONS, self::KEY_HASH_LENGTH, true);
            $crypt = base64_encode(openssl_encrypt($plain, self::METHOD, $key, OPENSSL_RAW_DATA, $iv));
            $hash = self::digest($crypt, md5($passphrase));
            $data =  $crypt . '*' . bin2hex($salt) . '*' . bin2hex($iv) . '*' . $hash;            
            return base64_encode($data);
        }
        return false;
    }


    /**
     * Decrypt Data from client
     * @param string $paraphrase  Encryption Key
     * @param string $cipher Data to decrypt
     * @param string $iv
     * @return string|boolean
     */
    public static function decrypt($passphrase, $cipher)
    {
        try {
            if (!empty($passphrase) && ($data = explode('*', base64_decode($cipher)))) {
                $crypt = $data[0] ?? null;
                $salt = $data[1] ?? null;
                $iv  = $data[2] ?? null;
                $hash  = $data[3] ?? null;
                if ($crypt && $salt && $iv && $hash) {
                    $salt = hex2bin($salt);
                    $iv = hex2bin($iv);
                    $key = hash_pbkdf2(self::KEY_HASH_ALGO, $passphrase, $salt, self::KEY_HASH_INTERATIONS, self::KEY_HASH_LENGTH, true);
                    if ($hash == self::digest($crypt, md5($passphrase))) {
                        return openssl_decrypt(base64_decode($crypt), self::METHOD, $key, OPENSSL_RAW_DATA, $iv);
                    }
                }
            }
            return false;
        } catch (Throwable $e) {
            return false;
        }
    }


    /**
     * Generate hmac signature for data
     * @param string $data String Data 
     * @param string $key hmac key
     * @return string|boolean
     */
    public static function digest($data, $key)
    {
        return hash_hmac(self::HMAC_HASH_ALGO, $data, $key);
    }
}
