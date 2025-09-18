<?php

namespace Access\Security\Authenticators;

use Access\Entity\User;
use Authentication\SAML\XDSamlAuthentication;
use Models\Services\Organizations;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\PreAuthenticatedUserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

use SimpleSAML\Auth\Source;

use XDUser;

class SimpleSamlPhpAuthenticator extends AbstractAuthenticator implements AuthenticatorInterface, AuthenticationEntryPointInterface
{
    private LoggerInterface $logger;

    private HttpUtils $httpUtils;

    private UrlGeneratorInterface $urlGenerator;


    private array $sources;

    private string $authSourceName;
    private \SimpleSAML\Auth\Simple $authSource;

    private ContainerBagInterface $parameters;

    public function __construct(LoggerInterface $logger, HttpUtils $httpUtils, UrlGeneratorInterface $urlGenerator, ContainerBagInterface $parameters )
    {
        $this->logger = $logger;
        $this->httpUtils = $httpUtils;
        $this->urlGenerator = $urlGenerator;
        $this->parameters = $parameters;

        $this->sources = Source::getSources();
        $this->logger->debug('Auth Sources', [$this->sources]);
        if (!empty($this->sources)) {
            try {
                $authSource = \xd_utilities\getConfiguration('authentication', 'source');
                $this->logger->debug('Found Auth Source', [$authSource]);
            } catch (\Exception $e) {
                $authSource = null;
            }
            if (!is_null($authSource) && array_search($authSource, $this->sources) !== false) {
                $this->authSourceName = $authSource;
                $this->authSource = new \SimpleSAML\Auth\Simple($authSource);
            } else {
                $this->authSourceName = $this->sources[0];
                $this->authSource = new \SimpleSAML\Auth\Simple($this->authSourceName);
            }
        }
    }


    public function supports(Request $request): ?bool
    {
        $referer = $request->headers->get('referer');
        $this->logger->info('Checking if Authenticator supports request', [$referer]);
        return $referer === $this->parameters->get('sso')['login_link'];
    }

    public function authenticate(Request $request): Passport
    {
        if ($this->authSource->isAuthenticated()) {
            $attributes = $this->authSource->getAttributes();
            $username = $attributes['username'][0];
            $logger = $this->logger;
            return new SelfValidatingPassport(
                new UserBadge(
                    $username,
                    function($userName, $samlAttributes) use ($logger) {
                        $logger->debug('Loading SimpleSAMLPHP User');

                        function getOrganizationId($samlAttrs, $personId)
                        {
                            if ($personId !== -1 ) {
                                return Organizations::getOrganizationIdForPerson($personId);
                            } elseif(!empty($samlAttrs['organization'])) {
                                return Organizations::getIdByName($samlAttrs['organization'][0]);
                            }
                            return -1;
                        }

                        $xdmodUserId = \XDUser::userExistsWithUsername($userName);
                        $logger->debug('XDMoD UserID ', [$xdmodUserId]);
                        if ($xdmodUserId !== INVALID) {
                            $user = \XDUser::getUserByID($xdmodUserId);
                            $user->setSSOAttrs($samlAttributes);
                            return User::fromXDUser($user);
                        }
                        $logger->debug('Creating New SSO User!');
                        // If we've gotten this far then we're creating a new user. Proceed with gathering the
                        // information we'll need to do so.
                        $emailAddress = isset($samlAttributes['email_address']) ? $samlAttributes['email_address'][0] : NO_EMAIL_ADDRESS_SET;
                        $systemUserName = isset($samlAttributes['system_username']) ? $samlAttributes['system_username'][0] : $userName;
                        $firstName = isset($samlAttributes['first_name']) ? $samlAttributes['first_name'][0] : 'UNKNOWN';
                        $middleName = isset($samlAttributes['middle_name']) ? $samlAttributes['middle_name'][0] : null;
                        $lastName = isset($samlAttributes['last_name']) ? $samlAttributes['last_name'][0] : null;
                        $personId = \DataWarehouse::getPersonIdFromPII($systemUserName, $samlAttributes['organization'][0]);

                        // Attempt to identify which organization this user should be associated with. Prefer
                        // using the personId if not unknown, then fall back to the saml attributes if the
                        // 'organization' property is present, and finally defaulting to the Unknown organization
                        // if none of the preceding conditions are met.
                        $userOrganization = getOrganizationId($samlAttributes, $personId);

                        try {
                            $newUser = new \XDUser(
                                $userName,
                                null,
                                $emailAddress,
                                $firstName,
                                $middleName,
                                $lastName,
                                array(ROLE_ID_USER),
                                ROLE_ID_USER,
                                $userOrganization,
                                $personId,
                                $samlAttributes
                            );
                        } catch (\Exception $e) {
                            throw new \Exception('An account is currently configured with this information, please contact an administrator.');
                        }

                        $newUser->setUserType(SSO_USER_TYPE);

                        try {
                            $newUser->saveUser();
                        } catch (\Exception $e) {
                            $this->logger->error('User creation failed: ' . $e->getMessage());
                            throw $e;
                        }

                        return User::fromXDUser($newUser);
                    },
                    $attributes
                )
            );
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $this->logger->info('SimpleSAMLPHP Authentication Succeeded!');
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->logger->info('SimpleSAMLPHP Authentication Failed!', [$exception]);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->urlGenerator->generate('xdmod_home'));
    }
}
