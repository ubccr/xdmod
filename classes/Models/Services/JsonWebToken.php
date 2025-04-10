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
    const

    public static signingAlgorithm = 'HS256';

    private static claimKeyIssuedAtTime = 'iat';
    private static claimKeyTokenId      = 'jti';
    private static claimKeyExpiration   = 'exp';
    private static claimKeySubject      = 'sub';
    private static claimKeyAudience     = 'aud';
    private static claimKeyIssuer       = 'iss';

    private $_claimsSet;
    private $_secretKey;

    public function __construct($claims = array()) {
        $configuredSecretKey = \xd_utilities\getConfiguration('json_web_token', 'secret_key');
        $this->$_secretKey = new Key($configuredSecretKey, $this->signingAlgorithm);
        $this->$_claimsSet = $claims;
    }

    public function encode() {

        $xdmodURL   = \xd_utilities\getConfiguration('general', 'site_address');
        $issuedAt   = new \DateTimeImmutable()->getTimestamp();
        $expire     = $issuedAt->modify('+6 minutes')->getTimestamp();

        $claims = [
            JsonWebToken::claimKeyIssuedAtTime  => $issuedAt->getTimestamp(),
            JsonWebToken::claimKeyTokenId       => $base64_encode(random_bytes(16)),
            JsonWebToken::claimKeyExpiration    => $expire,
            JsonWebToken::claimKeyAudience      => $xdmodURL,
            JsonWebToken::claimKeyIssuer        => $xdmodURL
        ];

        $this->addClaims($claims);

        return JWT::encode(
            $this->$_claimsSet,
            $this->$_secretKey,
            $this->$signingAlgorithm
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
        array_push($this->_claimsSet, $claims);
    }

    public function getClaim($claimKey) {
        if (array_key_exists($claimKey, $this->$_claimsSet)) {
            return $this->$_claimsSet[$claimKey];
        } else {
            throw new Exception('JSON Web Token Claim does not exist.');
        }
    }
