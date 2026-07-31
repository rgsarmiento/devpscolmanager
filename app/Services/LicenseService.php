<?php

namespace App\Services;

class LicenseService
{
    private const SECRET_KEY = '$N0D0$';

    /**
     * @param string $pin
     * @return string 24-byte key for 3DES
     */
    private static function getDesKey(string $pin = ''): string
    {
        $keyStr = $pin . self::SECRET_KEY;
        // Compute MD5 hash and return raw binary (16 bytes)
        $md5Hash = md5($keyStr, true);
        
        // VB.NET TripleDES with a 16-byte key uses Two-Key 3DES.
        // We append the first 8 bytes of the hash to make it a 24-byte key for OpenSSL.
        return $md5Hash . substr($md5Hash, 0, 8);
    }

    public static function encrypt(string $stringToEncrypt, string $pin = ''): string
    {
        $key = self::getDesKey($pin);
        
        // ECB mode does not use an IV.
        // OPENSSL_RAW_DATA ensures it returns raw bytes, which we then base64 encode.
        $encrypted = openssl_encrypt($stringToEncrypt, 'des-ede3-ecb', $key, OPENSSL_RAW_DATA);
        
        return base64_encode($encrypted);
    }

    public static function decrypt(string $encryptedString, string $pin = ''): string
    {
        try {
            $key = self::getDesKey($pin);
            $decoded = base64_decode($encryptedString);
            
            $decrypted = openssl_decrypt($decoded, 'des-ede3-ecb', $key, OPENSSL_RAW_DATA);
            
            if ($decrypted === false) {
                return 'na';
            }
            
            return $decrypted;
        } catch (\Exception $e) {
            return 'na';
        }
    }
}
