<?php

namespace App\Services;

class JwtService
{
    private static $secret = 'vishwakarmaskey1234567890!@#';

    /**
     * Generate a signed JWT token.
     */
    public static function generateToken(array $payload, int $expirySeconds = 86400): string
    {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload['exp'] = time() + $expirySeconds;
        $payload['iat'] = time();
        
        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::$secret, true);
        $base64UrlSignature = self::base64UrlEncode($signature);
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    /**
     * Decode and verify a JWT token. Returns null if invalid or expired.
     */
    public static function decodeToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        
        list($header, $payload, $signature) = $parts;
        
        $validSignature = hash_hmac('sha256', $header . "." . $payload, self::$secret, true);
        $base64UrlValidSignature = self::base64UrlEncode($validSignature);
        
        if (!hash_equals($signature, $base64UrlValidSignature)) {
            return null;
        }
        
        $decodedPayload = json_decode(self::base64UrlDecode($payload), true);
        if (!$decodedPayload || (isset($decodedPayload['exp']) && time() > $decodedPayload['exp'])) {
            return null;
        }
        
        return $decodedPayload;
    }

    private static function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}
