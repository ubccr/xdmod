<?php
declare(strict_types=1);

namespace CCR\Controller;

use CCR\Helper\PasswordResetService;
use CCR\Security\Helpers\Tokens;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;
use XDUser;

use function xd_response\buildError;

/**
 * This class encapsulates the operations previously provided by /controllers/user_auth.php which at this point is just:
 * - pass_reset
 */
class UserAuthController extends BaseController
{

    protected PasswordResetService $passwordResetService;

    public function __construct(LoggerInterface $logger, Environment $twig, Tokens $tokenHelper, ContainerBagInterface $parameters, PasswordResetService $passwordResetService)
    {
        parent::__construct($logger, $twig, $tokenHelper, $parameters);
        $this->passwordResetService = $passwordResetService;
    }

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
        $returnData = [];
        $email = $this->getEmailParam($request, 'email');

        if (empty($email)) {
            $returnData['status'] = 'invalid_email_address';
            return $this->json($returnData);
        };

        $user_to_email = XDUser::userExistsWithEmailAddress($email, true);

        if ($user_to_email == INVALID) {
            $returnData['status'] = 'no_user_mapping';
            return $this->json($returnData);
        }

        if ($user_to_email == AMBIGUOUS) {
            $returnData['status'] = 'multiple_accounts_mapped';
            return $this->json($returnData);
        }

        $user_to_email = XDUser::getUserByID($user_to_email);
        try {
            $this->passwordResetService->sendPasswordResetEmail($user_to_email);
            $returnData['success'] = true;
            $returnData['status'] = 'success';
        } catch (\Exception|\Throwable $e) {
            $returnData['success'] = false;
            $returnData['message'] = $e->getMessage();
            $returnData['status'] = 'failure';
        }

        return $this->json($returnData);
    }
}
