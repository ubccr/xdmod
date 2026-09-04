<?php

namespace Models\Services;

use DateTimeImmutable;
use DomainException;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\CachedKeySet;
use Firebase\JWT\Key;
use InvalidArgumentException;

class JsonWebToken
{
    const SIGNING_ALGORITHM = 'RS256';
    const KEYS_DIR = (
        CONFIG_DIR
        . DIRECTORY_SEPARATOR
        . 'keys'
        . DIRECTORY_SEPARATOR
    );
    const XDMOD_PRIVATE_KEY_FILE = self::KEYS_DIR . 'xdmod-private.pem';
    const JUPYTERHUB_PUBLIC_KEY_FILE = self::KEYS_DIR . 'jupyterhub-public.pem';
    const JWKS_ENDPOINT = '/.well-known/jwks.json';

    /**
     * @param string $subject the 'sub' property of the JWT to encode.
     * @return array first element is a signed JWT, second is the expiration
     *               time of the JWT.
     */
    public static function encode($subject) {
        $xdmodPrivateKey = file_get_contents(self::XDMOD_PRIVATE_KEY_FILE);
        if (false === $xdmodPrivateKey) {
            throw new Exception(
                'This XDMoD portal is missing a private key at `'
                . self::XDMOD_PRIVATE_KEY_FILE
                . '` for signing JSON Web Tokens.'
            );
        }
        $issuedAt = new DateTimeImmutable();
        $expiration = $issuedAt->modify('+30 seconds')->getTimestamp();
        try {
            $jwt = JWT::encode(
                [
                    'exp' => $expiration,
                    'sub' => $subject
                ],
                $xdmodPrivateKey,
                self::SIGNING_ALGORITHM
            );
        } catch (DomainException $e) {
            throw new Exception(
                'Error signing the JSON Web Token using `'
                . self::XDMOD_PRIVATE_KEY_FILE
                . '`.'
            );
        }
        return [$jwt, $expiration];
    }

    /**
     * @param string $jwt
     * @return \stdClass the claims in the JWT.
     */
    public static function decode($jwt) {
        $jupyterhubPublicKey = file_get_contents(self::JUPYTERHUB_PUBLIC_KEY_FILE);
        if (false === $jupyterhubPublicKey) {
            throw new Exception(
                'This XDMoD portal is missing a public key at `'
                . self::JUPYTERHUB_PUBLIC_KEY_FILE
                . '` for decoding JSON Web Tokens.'
            );
        }
        try {
            $secretKey = new Key($jupyterhubPublicKey, self::SIGNING_ALGORITHM);
            $claims = JWT::decode($jwt, $secretKey);
        } catch (InvalidArgumentException $e) {
            throw new Exception(
                'The public key file at `'
                . self::JUPYTERHUB_PUBLIC_KEY_FILE
                . '` is empty.'
            );
        }
        return $claims;
    }

    /**
     * @param string $jwt
     * @param array $jwks keys to decode jwt
     * @return \stdClass the claims in the JWT.
     */
    public static function decodeWithKeys($jwt, $jwks) {
        try {
            $claims = JWT::decode($jwt, $jwks);
        } catch (InvalidArgumentException $e) {
            throw new Exception('Unable to decode JWT with given JWKS');
        }
        return $claims;
    }

    /**
     * @param Request $request that will be used to retrieve the origin for the JWKS endpoint
     * @return array keys need to decode JWT.
     */
    public static function getJwks($request) {
        $jwksUri = $request->headers->get('Origin') . JsonWebToken::JWKS_ENDPOINT;

        // Create an HTTP client (can be any PSR-7 compatible HTTP client)
        $httpClient = new GuzzleHttp\Client();

        // Create an HTTP request factory (can be any PSR-17 compatible HTTP request factory)
        $httpFactory = new GuzzleHttp\Psr7\HttpFactory();

        // Create a cache item pool (can be any PSR-6 compatible cache item pool)
        $cacheItemPool = Phpfastcache\CacheManager::getInstance('files');

        return new CachedKeySet(
            $jwksUri,
            $httpClient,
            $httpFactory,
            $cacheItemPool,
            null, // $expiresAfter int seconds to set the JWKS to expire
            true  // $rateLimit    true to enable rate limit of 10 RPS on lookup of invalid keys
        );
    }
}
