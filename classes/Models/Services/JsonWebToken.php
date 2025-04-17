<?php

namespace Models\Services;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JsonWebToken
{
    const claimKeyIssuedAtTime = 'iat';
    const claimKeyTokenId      = 'jti';
    const claimKeyExpiration   = 'exp';
    const claimKeySubject      = 'sub';
    const claimKeyAudience     = 'aud';
    const claimKeyIssuer       = 'iss';

    public static $signingAlgorithm = 'HS256';
    private $_claimsSet;
    private $_secretKey;

    public function __construct($claims = array()) {
        $configuredSecretKey = \xd_utilities\getConfiguration('json_web_token', 'secret_key');
        $this->_secretKey = $configuredSecretKey; //new Key($configuredSecretKey, self::$signingAlgorithm);

        $xdmodURL   = \xd_utilities\getConfiguration('general', 'site_address');
        $issuedAt   = new \DateTimeImmutable();
        $expire     = $issuedAt->modify('+30 seconds')->getTimestamp();

        $this->_claimsSet = array_merge(
            [
                self::claimKeyIssuedAtTime  => $issuedAt->getTimestamp(),
                self::claimKeyTokenId       => base64_encode(random_bytes(16)),
                self::claimKeyExpiration    => $expire,
                self::claimKeyAudience      => $xdmodURL,
                self::claimKeyIssuer        => $xdmodURL
            ],
            $claims
        );
    }

    public function encode() {
        return JWT::encode(
            $this->_claimsSet,
            $this->_secretKey,
            self::$signingAlgorithm
        );
    }

    public function decode($jwt) {
        try {
            $secretKey = new Key($this->_secretKey, self::$signingAlgorithm);
            $decodedToken = JWT::decode($jwt, $secretKey);
        } catch (Exception $e) {
            throw new Exception('Error while decoding: '.$e->getMessage());
        }
        $decodedToken = (array) $decodedToken;
        $this->addClaims($decodedToken);
    }

    /**
     * Add one or more claim to the claims set
     */
    public function addClaims($claims) {
        foreach ($claims as $key => $value) {
            $this->_claimsSet[$key] = $value;
        }
    }

    public function getClaim($claimKey) {
        if (array_key_exists($claimKey, $this->_claimsSet)) {
            return $this->_claimsSet[$claimKey];
        } else {
            throw new Exception('JSON Web Token Claim does not exist.');
        }
    }
}
