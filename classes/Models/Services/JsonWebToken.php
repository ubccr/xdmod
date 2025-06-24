<?php

namespace Models\Services;

use DateTimeImmutable;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JsonWebToken
{
    const SIGNING_ALGORITHM = 'RS256';

    private static $xdmodPrivateKey = null;
    private static $jupyterhubPublicKey = null;

    /**
     * @param string $subject
     * @return array first element is a signed JWT, second is the expiration
     *               time of the JWT.
     */
    public static function encode($subject) {
        if (is_null(self::$xdmodPrivateKey)) {
            self::$xdmodPrivateKey = file_get_contents(
                CONFIG_DIR
                . DIRECTORY_SEPARATOR
                . 'xdmod-private.pem'
            );
        }
        $issuedAt = new DateTimeImmutable();
        $expiration = $issuedAt->modify('+30 seconds')->getTimestamp();
        $jwt = JWT::encode(
            [
                'exp' => $expiration,
                'sub' => $subject
            ],
            self::$xdmodPrivateKey,
            self::SIGNING_ALGORITHM
        );
        return [$jwt, $expiration];
    }

    /**
     * @param string $jwt
     * @return \stdClass the claims in the JWT.
     */
    public static function decode($jwt) {
        if (is_null(self::$jupyterhubPublicKey)) {
            self::$jupyterhubPublicKey = file_get_contents(
                CONFIG_DIR
                . DIRECTORY_SEPARATOR
                . 'jupyterhub-public.pem'
            );
        }
        $secretKey = new Key(self::$jupyterhubPublicKey, self::SIGNING_ALGORITHM);
        return JWT::decode($jwt, $secretKey);
    }
}
