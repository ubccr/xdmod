<?php

namespace CCR\Controller\InternalDashboard;

use CCR\DB;
use CCR\MailWrapper;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use function xd_response\buildError;

class MailerController extends \CCR\Controller\BaseController
{
    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('/internal_dashboard/controllers/mailer.php', name: 'legacy_dashboard_mailer_index', methods: ['POST'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->authorize($request, ['mgr']);
        $operation = $this->getStringParam($request, 'operation', true);

        switch ($operation) {
            case 'enum_target_addresses':
                return $this->enumTargetAddresses($request);
            case 'send_plain_mail':
                return $this->sendPlainMail($request);
            default:
                throw new BadRequestHttpException('Unknown operation.');
        }
    }

    /**
     * This is a straight port of `internal_dashboard/controllers/mailer.php` w/ enum_target_addresses operation.
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    private function enumTargetAddresses(Request $request): Response
    {
        $groupFilter = $this->getStringParam($request, 'group_filter');
        if (is_null($groupFilter)) {
            $groupFilter = $this->getIntParam($request, 'group_filter');
            if (is_null($groupFilter)) {
                return $this->json(buildError("'group_filter' not specified."));
            }
        }

        $aclFilter = $this->getStringParam($request, 'role_filter');
        if (is_null($aclFilter)) {
            $aclFilter = $this->getIntParam($request, 'role_filter');
            if (is_null($aclFilter)) {
                return $this->json(buildError("'role_filter' not specified."));
            }
        }


        list($query, $params) = \xd_dashboard\listUserEmailsByGroupAndAcl($groupFilter, $aclFilter);

        $db = DB::factory('database');
        $results = $db->query($query, $params);

        $addresses = array();

        foreach ($results as $r) {
            $addresses[] = $r['email_address'];
        }

        sort($addresses);

        return $this->json([
            'success' => true,
            'count' => count($addresses),
            'response' => $addresses
        ]);
    }

    /**
     * This is just a straight port of `internal_dashboard/controllers/mailer.php` w/ operation send_plain_mail.
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    private function sendPlainMail(Request $request): Response
    {
        $title = \xd_utilities\getConfiguration('general', 'title');
        $contactPageRecipient = \xd_utilities\getConfiguration('general', 'contact_page_recipient');

        $message = $this->getStringParam($request, 'message', true, null, '/.*/', false);
        $subject = $this->getStringParam($request, 'subject', true);
        $targetAddresses = $this->getStringParam($request, 'target_addresses');

        MailWrapper::sendMail([
            'body' => $message,
            'subject' => "[$title] " . $subject,
            'toAddress' => $contactPageRecipient,
            'toName' => 'Undisclosed Recipients',
            'fromAddress' => $contactPageRecipient,
            'fromName' => $title,
            'bcc' => $targetAddresses
        ]);

        return $this->json([
            'success' => true
        ]);
    }
}
