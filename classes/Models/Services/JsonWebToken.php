<?php

namespace Models\Services;

use DateTimeImmutable;
use DomainException;
use Exception;
use Firebase\JWT\JWT;
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
}
