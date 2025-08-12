<?php

namespace Access\Security;

use Access\Entity\User;
use CCR\MailWrapper;
use Models\Services\Organizations;
use Nbgrp\OneloginSamlBundle\Security\User\SamlUserFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use XDUser;

class SSOUserFactory implements SamlUserFactoryInterface
{

    private const USERNAME_PROPERTIES = ['itname', 'system_username'];

    private const BASE_ADMIN_EMAIL = <<<EML

Person Details -----------------------------------
Name:              %s
Username:          %s
E-Mail:            %s

SAML Attributes ----------------------------------
%s

Notes: -------------------------------------------

Unable to Identify Users Organization.

Additional Setup Required.
EML;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ContainerBagInterface
     */
    private $parameters;

    public function __construct(LoggerInterface $logger, ContainerBagInterface $parameters)
    {
        $this->logger = $logger;
        $this->parameters = $parameters;
    }

    /**
     * @inheritDoc
     */
    public function createUser($identifier, array $attributes = []): UserInterface
    {
        $this->logger->warning("Creating User $identifier");
        $ssoSettings = $this->parameters->get('sso');
        if (empty($ssoSettings) || !array_key_exists('parameters', $ssoSettings)) {
            $this->logger->debug(var_export($ssoSettings, true));
            throw new \Exception('Required SSO settings not present, unable to continue.');
        }

        $ssoParameters = $ssoSettings['parameters'];
        $this->logger->debug('Creating User With Parameters', [$ssoParameters]);

        $attributes = self::extractAttributes($ssoParameters, $attributes);
        $this->logger->debug('With Attributes ', [$attributes]);

        $systemUserName = $attributes['system_username'];
        $organization = $attributes['organization'];
        $personId = \DataWarehouse::getPersonIdFromPII($systemUserName, $organization);
        $organizationId = $this->getOrganizationId($personId, $organization);
        $this->logger->debug('Creating User with Organization Id: ', [$organizationId]);
        try {
            $newUser = new \XDUser(
                $identifier,
                null,
                $attributes['email_address'],
                $attributes['first_name'],
                $attributes['middle_name'],
                $attributes['last_name'],
                array(ROLE_ID_USER),
                ROLE_ID_USER,
                $organizationId,
                $personId,
                $attributes,
                false,
                null
            );
        } catch (\Exception $e) {
            $this->logger->error('User creation failed: ' . $e->getMessage());
            throw $e;
        }

        $newUser->setUserType(SSO_USER_TYPE);

        try {
            $newUser->saveUser();
            $this->logger->debug('Created User w/ Organization Id', [$newUser->getOrganizationID()]);
        } catch (\Exception $e) {
            $this->logger->error('User failed to save: ' . $e->getMessage());
            throw $e;
        }

        $this->handleNotifications($newUser, $attributes, ($personId != UNKNOWN_USER_TYPE));

        return User::fromXDUser($newUser);
    }


    /**
     * @param array|string $keys
     * @param array $attributes
     * @param mixed $default
     * @param int $index
     * @return ?string
     */
    private static function get($keys, array $attributes, $default = null, int $index = 0): ?string
    {
        if (is_string($keys) && array_key_exists($keys, $attributes)) {
            return $attributes[$keys][$index];
        }
        if (is_array($keys) && count($keys) > 0) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $attributes)) {
                    return $attributes[$key][$index];
                }
            }
        }
        return $default;
    }

    private function getOrganizationId($personId, $organization): int
    {
        if (!empty($personId) && $personId > 0) {
            $organizationId = Organizations::getOrganizationIdForPerson($personId);
            $this->logger->debug('Getting Organization Id For Person', [$personId, $organizationId]);
            return $organizationId;
        } elseif (!empty($organization)) {
            $organizationId = Organizations::getIdByName($organization);
            $this->logger->debug('Getting Organization Id For Organization Name', [$organization, $organizationId]);
            return $organizationId;
        }
        return -1;
    }

    /**
     * Determine / handle any notifications that may be required as a part of this SSO User -> XDMoD
     * User operation.
     *
     * @param XDUser $user the XDMoD User instance to be used during notification.
     * @param array $samlAttributes the attributes that we received via SAML for this user.
     * @param boolean $linked whether or not we were able to link the SSO User with an XDMoD Person.
     * @throws \Exception if there is a problem with notifying the user.
     */
    private function handleNotifications(XDUser $user, $samlAttributes, $linked)
    {
        if ($user->getOrganizationID() !== -1) {
            // Only send email if we were unable to identify an organization for the user.
            return;
        }

        $subject = sprintf(
            'New %s Single Sign On User Created',
            ($linked ? 'Linked' : 'Unlinked')
        );

        $body = sprintf(
            self::BASE_ADMIN_EMAIL,
            $user->getFormalName(true),
            $user->getUsername(),
            $user->getEmailAddress(),
            json_encode($samlAttributes, JSON_PRETTY_PRINT)
        );

        try {
            MailWrapper::sendMail(
                array(
                    'subject' => $subject,
                    'body' => $body,
                    'toAddress' => \xd_utilities\getConfiguration('general', 'tech_support_recipient')
                )
            );
        } catch (\Exception $e) {
            // log the exception so we have some persistent visibility into the problem.
            $errorMsgFormat = "[%s] %s\n%s";
            $errorMsg = sprintf(
                $errorMsgFormat,
                $e->getCode(),
                $e->getMessage(),
                $e->getTraceAsString()
            );

            $this->logger->error("Error encountered while emailing\n$errorMsg");

            throw $e;
        }
    }

    /**
     * @param $ssoParameters
     * @param array $attributes
     * @return array
     * @throws \Exception
     */
    public static function extractAttributes($ssoParameters, array $attributes): array
    {
        $results = [];
        foreach ($ssoParameters as $parameter => $parameterData) {
            $attribute = $parameterData['attribute'];
            $default = !empty($parameter['default']) ? $parameter['default'] : null;
            if (!empty($default) && substr($default, 0) === '$') {
                // Then we want to use a previously retrieved value as the default.
                $key = substr($default, 1, strlen($default) - 1);
                // if it exists, then use it, else the default will be null
                if (array_key_exists($key, $results)) {
                    $default = $results[$key];
                }
                throw new \Exception(sprintf('Unable to find referenced value %s for use as a default value', $key));
            }
            $results[$parameter] = self::get($attribute, $attributes, $default);
        }
        return $results;
    }
}
