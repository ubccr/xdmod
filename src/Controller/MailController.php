<?php

namespace CCR\Controller;

use CCR\DB;
use CCR\MailWrapper;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use XDUser;

/**
 */
class MailController extends BaseController
{
    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('/controllers/mailer.php', methods: ['POST'], name: 'legacy_mailer_index')]
    public function index(Request $request): Response
    {
        $user = $this->getUserFromRequest($request);
        $operation = $this->getStringParam($request, 'operation', true);

        switch ($operation) {
            case 'contact':
                return $this->contact($request, $user);
            case 'sign_up':
                return $this->signUp($request);
            default:
                throw new BadRequestHttpException('invalid operation specified');
        }
    }

    /**
     * Takes the place of the old html/controllers/mailer/contact.php
     *
     * @param Request $request
     * @param ?XDUser $user
     * @return Response
     */
    private function contact(Request $request, ?XDUser $user): Response
    {
        if (!isset($user)) {
            $user = XDUser::getPublicUser();
        }

        $name = $this->getStringParam($request, 'name', true, null, RESTRICTION_FIRST_NAME);
        // This variable is overwritten before it is used. I'm leaving it here for now but it should be removed after
        // the rest stack migration is complete.
        $message = $this->getStringParam($request, 'message', true, null, RESTRICTION_NON_EMPTY);
        $username = $this->getStringParam($request, 'username', true, null, RESTRICTION_NON_EMPTY);
        $token = $this->getStringParam($request, 'token', true, null, RESTRICTION_NON_EMPTY);
        $timestamp = $this->getStringParam($request, 'timestamp', true, null, RESTRICTION_NON_EMPTY);
        $email = $this->getEmailParam($request, 'email', true);
        $reason = $this->getStringParam($request, 'reason', false, 'contact');

        $userInfo = $user->isPublicUser() ? 'Public Visitor' : "Username:     $username";

        $this->verifyCaptcha($request);

        switch ($reason) {
            case 'wishlist':
                $subject = '[WISHLIST] Feature request sent from a portal visitor';
                $message_type = 'feature request';
                break;

            default:
                $subject = 'Message sent from a portal visitor';
                $message_type = 'message';
                break;
        }
        $timestamp = date('m/d/Y, g:i:s A', $timestamp);
        $message = "Below is a $message_type from '$name' ($email):\n\n";
        $message .= $message;
        $message .= "\n------------------------\n\nSession Tracking Data:\n\n  ";
        $message .= "$userInfo\n\n  Token:        $token\n  Timestamp:    $timestamp";

        try {
            //Original sender's e-mail must be in the 'fromAddress' field for the XDMoD Request Tracker to function
            MailWrapper::sendMail(array(
                    'body' => $message,
                    'subject' => $subject,
                    'toAddress' => \xd_utilities\getConfiguration('general', 'contact_page_recipient'),
                    'fromAddress' => $_POST['email'],
                    'fromName' => $_POST['name']
                )
            );
        } catch (Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $message
            ]);
        }

        $message
            = "Hello, $name\n\n"
            . "This e-mail is to inform you that the XDMoD Portal Team has received your $message_type, and will\n"
            . "be in touch with you as soon as possible.\n\n"
            . MailWrapper::getMaintainerSignature();

        try {
            MailWrapper::sendMail(array(
                    'body' => $message,
                    'subject' => "Thank you for your $message_type.",
                    'toAddress' => $_POST['email']
                )
            );
        } catch (Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $message
            ]);
        }
        return $this->json([
            'success' => true
        ]);
    }

    /**
     * Takes the place of the old html/controllers/mailer/sign_up.php
     * @param Request $request
     * @return Response
     * @throws Exception if unable to contact the database.
     */
    private function signUp(Request $request): Response
    {
        $firstName = $this->getStringParam($request, 'first_name', true, null, RESTRICTION_FIRST_NAME);
        $lastName = $this->getStringParam($request, 'last_name', true, null, RESTRICTION_LAST_NAME);
        $title = $this->getStringParam($request, 'title', true, null, RESTRICTION_NON_EMPTY);
        $organization = $this->getStringParam($request, 'organization', true, null, RESTRICTION_NON_EMPTY);
        $fieldOfScience = $this->getStringParam($request, 'field_of_science', true, null, RESTRICTION_NON_EMPTY);
        $additionalInformation = $this->getStringParam($request, 'additional_information', true, null, RESTRICTION_NON_EMPTY);
        $email = $this->getEmailParam($request, 'email', true);

        $this->verifyCaptcha($request);

        // Insert account request into database (so it appears in the internal
        // dashboard under "XDMoD Account Requests").
        $pdo = DB::factory('database');

        $pdo->execute(
            "
        INSERT INTO AccountRequests (
            first_name,
            last_name,
            organization,
            title,
            email_address,
            field_of_science,
            additional_information,
            time_submitted,
            status,
            comments
        ) VALUES (
            :first_name,
            :last_name,
            :organization,
            :title,
            :email_address,
            :field_of_science,
            :additional_information,
            NOW(),
            'new',
            ''
        )
    ",
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'organization' => $organization,
                'title' => $title,
                'email_address' => $email,
                'field_of_science' => $fieldOfScience,
                'additional_information' => $additionalInformation
            ]
        );

        // Create email.

        $time_requested = date('D, F j, Y \a\t g:i A');
        $organization = ORGANIZATION_NAME;

        $message = <<<MSG
The following person has signed up for an account on XDMoD:

Person Details ----------------------------------

Name:                     $firstName $lastName
E-Mail:                   $email
Title:                    $title
Organization:             $organization

Time Account Requested:   $time_requested

Affiliation with $organization:

$additionalInformation
MSG;


        $response = [];

        // Original sender's e-mail must be in the "fromAddress" field for the XDMoD Request Tracker to function
        try {
            MailWrapper::sendMail([
                'body' => $message,
                'subject' => '[' . \xd_utilities\getConfiguration('general', 'title') . '] A visitor has signed up',
                'toAddress' => \xd_utilities\getConfiguration('general', 'contact_page_recipient'),
                'fromAddress' => $_POST['email'],
                'fromName' => $_POST['last_name'] . ', ' . $_POST['first_name']
            ]);
            $response['success'] = true;
        } catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }

        return $this->json($response);
    }
}
