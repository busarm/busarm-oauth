<?php
defined('OAUTH_BASE_PATH') OR exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 12/31/2017
 * Time: 12:51 PM
 */


class CIPHER
{

    private static $method = "AES-256-CBC"; //Never Change!!

    /**
     * Encrypt Data for client
     * @param string $paraphrase  Encryption Key
     * @param string $plain Data to encrypt
     * @param string $iv
     * @return mixed Base64 encoded result of encrypted data
     */
    public static function encrypt($passphrase, $plain) {
        if (!empty($passphrase)) {
            $salt = openssl_random_pseudo_bytes(128);
            $iv = openssl_random_pseudo_bytes(16);
            $key = hash_pbkdf2("sha512", $passphrase, $salt, 100, 64, true);
            $ciphertext = base64_encode(openssl_encrypt($plain, CIPHER::$method, $key, OPENSSL_RAW_DATA, $iv));
            $hash = self::getDigest($ciphertext, md5($passphrase));
            $data = array("ciphertext" => $ciphertext, "iv" => bin2hex($iv), "salt" => bin2hex($salt),  "hash" => $hash);
            return base64_encode(json_encode($data));
        }
        return false;
    }


    /**
     * Decrypt Data from client
     * @param string $paraphrase  Encryption Key
     * @param string $cipher  Data to decrypt
     * @param string $iv
     * @return string|boolean
     */
    public static function decrypt($passphrase, $cipher) {
        if (!empty($passphrase) && ($data = json_decode(base64_decode($cipher), true))) {
            try {
                $ciphertext = $data["ciphertext"];
                $salt = hex2bin($data["salt"]);
                $iv  = hex2bin($data["iv"]);  
                $hash  = $data["hash"];          
            } catch(Throwable $e) { return false; }
            $key = hash_pbkdf2("sha512", $passphrase, $salt, 100, 64, true);
            if ($hash == self::getDigest($ciphertext, md5($passphrase))){
                return openssl_decrypt(base64_decode($ciphertext) , CIPHER::$method, $key, OPENSSL_RAW_DATA, $iv);
            }
        }
        return false;
    }

    
    /**
     * Generate hmac signature for data
     * @param string $data String Data 
     * @param string $key hmac key
     * @return string|boolean
     */
    public static function getDigest($data, $key){
        return hash_hmac("sha256", $data, $key);
    }
}