<?php declare(strict_types=1);
namespace CCR\Helper;

use CCR\MailWrapper;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Twig\Environment;
use XDUser;
use function xd_utilities\string_ends_with;

class PasswordResetService
{

    public function __construct(
        protected LoggerInterface $logger,
        protected Environment $twig,
        protected ContainerBagInterface $parameters
    ) {
    }

    /**
     * Send a password reset to the specified XDMoD $user.
     *
     * @param XDUser $user
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface if there is a problem with the underlying Symfony container
     * @throws \Psr\Container\NotFoundExceptionInterface if unable to find portal_settings.general.[title|site_address]
     * @throws \Twig\Error\LoaderError if there is a problem loading the password_reset twig template
     * @throws \Twig\Error\RuntimeError if Twig encounters an error while rendering the template.
     * @throws \Twig\Error\SyntaxError if there is a syntax problem with the password_reset twig template.
     * @throws \Exception if XDUser is unable to find portal_settings.general.[email_token_expiration|application_secret]
     * @throws \Exception if MailWrapper encounters an error while attempting to send the password reset email.
     */
    public function sendPasswordResetEmail(XDUser $user)
    {
        $rid = $user->generateRID();

        $title = $this->parameters->get('xdmod.portal_settings.general.title');
        $subject = sprintf('%s: Password Reset', $title);
        $siteAddress = $this->parameters->get('xdmod.portal_settings.general.site_address');
        if (!string_ends_with($siteAddress, '/')) {
            $siteAddress = "$siteAddress/";
        }

        $body = $this->twig->render(
            'twig/emails/password_reset.html.twig',
            [
                'first_name' => $user->getFirstName(),
                'username' => $user->getUsername(),
                'reset_link' => sprintf('%scontrollers/password_reset.php?rid=%s', $siteAddress, $rid),
                'expiration' => date('%c %Z', (int)explode('|', $rid)[1]),
                'maintainer_signature' => MailWrapper::getMaintainerSignature(),
            ]
        );

        MailWrapper::sendMail([
            'toAddress' => $user->getEmailAddress(),
            'subject' => $subject,
            'body' => $body
        ]);
    }
}
