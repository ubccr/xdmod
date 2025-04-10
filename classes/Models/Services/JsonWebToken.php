<?php

namespace Models\Services;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use XDUser;

class JsonWebToken
{
    public static $signingAlgorithm = 'HS256';

    public static $claimKeyIssuedAtTime = 'iat';
    public static $claimKeyTokenId      = 'jti';
    public static $claimKeyExpiration   = 'exp';
    public static $claimKeySubject      = 'sub';
    public static $claimKeyAudience     = 'aud';
    public static $claimKeyIssuer       = 'iss';

    private $_claimsSet;
    private $_secretKey;

    public function __construct($claims = array()) {
        $configuredSecretKey = \xd_utilities\getConfiguration('json_web_token', 'secret_key');
        $this->_secretKey = $configuredSecretKey; //new Key($configuredSecretKey, self::$signingAlgorithm);
        $this->_claimsSet = $claims;
    }

    public function encode() {

        $xdmodURL   = \xd_utilities\getConfiguration('general', 'site_address');
        $issuedAt   = new \DateTimeImmutable();
        $expire     = $issuedAt->modify('+6 minutes')->getTimestamp();

        $claims = [
            self::claimKeyIssuedAtTime  => $issuedAt->getTimestamp(),
            self::claimKeyTokenId       => base64_encode(random_bytes(16)),
            self::claimKeyExpiration    => $expire,
            self::claimKeyAudience      => $xdmodURL,
            self::claimKeyIssuer        => $xdmodURL
        ];

        $this->addClaims($claims);

        return JWT::encode(
            $this->_claimsSet,
            $this->_secretKey,
            self::$signingAlgorithm
        );
    }

    public function decode($jwt) {
        try {
            $decodedToken = JWT::decode($jwt, $this->_secretKey);
        } catch (Exception $e) {
            throw new Exception('Error while decoding');
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
