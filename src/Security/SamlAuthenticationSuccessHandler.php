<?php

namespace Access\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\HttpUtils;
use XDUser;

class SamlAuthenticationSuccessHandler extends \Nbgrp\OneloginSamlBundle\Security\Http\Authentication\SamlAuthenticationSuccessHandler
{
    /**
     * @var ContainerBagInterface
     */
    private $parameters;

    public function __construct(HttpUtils $httpUtils, ContainerBagInterface $parameters, array $options = [], ?LoggerInterface $logger = null)
    {
        $this->parameters = $parameters;
        parent::__construct($httpUtils, $options, $logger);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): ?Response
    {
        $this->logger->warning('OnAutheneticationSuccess Called!!!!');
        $ssoSettings = $this->parameters->get('sso');
        $this->logger->warning('SSO Settings', [$ssoSettings]);
        if (empty($ssoSettings) || !array_key_exists('parameters', $ssoSettings)) {
            $this->logger->debug(var_export($ssoSettings, true));
            throw new \Exception('Required SSO settings not present, unable to continue.');
        }

        $ssoParameters = $ssoSettings['parameters'];

        $user = $token->getUser();
        $xdUser = XDUser::getUserByUserName($user->getUserIdentifier());

        $parameters = SSOUserFactory::extractAttributes($ssoParameters, $token->getAttributes());
        $this->logger->warning('SSO Attributes', [$parameters]);
        $xdUser->setSSOAttrs($parameters);

        $this->logger->debug('calling Post Login!!!');
        $xdUser->postLogin();
        $request->getSession()->set('xdUser', $xdUser->getUserID());
        return parent::onAuthenticationSuccess($request, $token);
    }
}
