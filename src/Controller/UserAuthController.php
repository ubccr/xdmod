<?php
declare(strict_types=1);

namespace CCR\Controller;

use CCR\MailWrapper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use XDUser;

use function xd_response\buildError;

/**
 * This class encapsulates the operations previously provided by /controllers/user_auth.php which at this point is just:
 * - pass_reset
 */
class UserAuthController extends BaseController
{

    #[Route('/controllers/user_auth.php', methods: ["POST"])]
    public function index(Request $request): Response
    {
        $operation = $this->getStringParam($request, 'operation');
        if (empty($operation)) {
            return $this->json(buildError('invalid_operation_specified'));
        }

        switch ($operation) {
            case 'pass_reset':
                return $this->requestPasswordReset($request);
            default:
                return $this->json(buildError('invalid_operation_specified'));
        }
    }

    /**
     * Request a password reset email be sent to a user who has an email corresponding to the one provided.
     *
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    private function requestPasswordReset(Request $request): Response
    {
        $email = $this->getEmailParam($request, 'email');

        if (empty($email)) {
            $returnData['status'] = 'invalid_email_address';
            return $this->json($returnData);
        };

        $user_to_email = XDUser::userExistsWithEmailAddress($email, TRUE);

        if ($user_to_email == INVALID) {
            $returnData['status'] = 'no_user_mapping';
            return $this->json($returnData);
        }

        if ($user_to_email == AMBIGUOUS) {
            $returnData['status'] = 'multiple_accounts_mapped';
            return $this->json($returnData);
        }

        $user_to_email = XDUser::getUserByID($user_to_email);

        $page_title = \xd_utilities\getConfiguration('general', 'title');

        try {
            $rid = $user_to_email->generateRID();

            $site_address
                = \xd_utilities\getConfigurationUrlBase('general', 'site_address');
            $resetUrl = "{$site_address}password_reset.php?rid=$rid";
            list($userId, $expiration, $hash) = explode('|', $rid);
            MailWrapper::sendTemplate(
                'password_reset',
                array(
                    'first_name' => $user_to_email->getFirstName(),
                    'username' => $user_to_email->getUsername(),
                    'reset_url' => $resetUrl,
                    'expiration' => date("%c %Z", (int)$expiration),
                    'maintainer_signature' => MailWrapper::getMaintainerSignature(),
                    'subject' => "$page_title: Password Reset",
                    'toAddress' => $user_to_email->getEmailAddress()
                )
            );
            $returnData['success'] = true;
            $returnData['status'] = 'success';
        } catch (\Exception $e) {
            $returnData['success'] = false;
            $returnData['message'] = $e->getMessage();
            $returnData['status'] = 'failure';
        }

        return $this->json($returnData);
    }
}
