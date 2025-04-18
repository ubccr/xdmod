<?php

namespace Models\Services;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JsonWebToken
{
    const claimKeyTokenId      = 'jti';
    const claimKeyExpiration   = 'exp';
    const claimKeySubject      = 'sub';

    public static $signingAlgorithm = 'RS256';
    private $_claimsSet;
    private $_jwtPrivateKey;

    public function __construct($claims = array()) {
        $this->_jwtPrivateKey = file_get_contents(
            CONFIG_DIR
            . DIRECTORY_SEPARATOR
            . 'xdmod-private.pem'
        );

        $xdmodURL   = \xd_utilities\getConfiguration('general', 'site_address');
        $issuedAt   = new \DateTimeImmutable();
        $expire     = $issuedAt->modify('+30 seconds')->getTimestamp();

        $this->_claimsSet = array_merge(
            [
                self::claimKeyTokenId       => base64_encode(random_bytes(16)),
                self::claimKeyExpiration    => $expire
            ],
            $claims
        );
    }

    public function encode() {
        return JWT::encode(
            $this->_claimsSet,
            $this->_jwtPrivateKey,
            self::$signingAlgorithm
        );
    }

    public function decode($jwt) {
        try {
            $secretKey = new Key($this->_jwtPrivateKey, self::$signingAlgorithm);
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
