<?php

declare(strict_types=1);

namespace CCR\Controller;

use Exception;
use Models\Services\Centers;
use Models\Services\Users;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use XDUser;
use function xd_response\buildError;

/**
 *
 */
class OrganizationController extends BaseController
{
    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('/controllers/role_manager.php')]
    public function index(Request $request): Response
    {
        $operation = $this->getStringParam(
            request: $request,
            name: 'operation',
            mandatory: true
        );

        if (in_array($operation, ['upgrade_member', 'downgrade_member'])) {
            try {
                $user = $this->authorize($request, [ROLE_ID_CENTER_DIRECTOR], true);
            } catch (Exception $e) {
                return $this->json(
                    [
                        "status" => "not_a_center_director",
                        "success" => false,
                        "totalCount" => 0,
                        "message" => "not_a_center_director",
                        "data" => []
                    ]
                );
            }
        }

        $forwardTo = null;
        switch($operation) {
            case 'downgrade_member':
                $memberID = $this->getValidMemberIDParameter($request);
                $forwardTo = $this->forward(
                    'CCR\Controller\OrganizationController::downgradeMember',
                    ['request' => $request, 'memberID' => $memberID]
                );
                break;
            case 'enum_center_staff_members':
                $forwardTo = $this->forward(
                    'CCR\Controller\OrganizationController::getMembers',
                    ['request' => $request]
                );
                break;
            case 'get_member_status':
                $memberID = $this->getValidMemberIDParameter($request);
                $forwardTo = $this->forward(
                    'CCR\Controller\OrganizationController::getMemberStatus',
                    ['request' => $request, 'memberID' => $memberID]
                );
                break;
            case 'upgrade_member':
                $memberID = $this->getValidMemberIDParameter($request);
                $forwardTo = $this->forward(
                    'CCR\Controller\OrganizationController::upgradeMember',
                    ['request' => $request, 'memberID' => $memberID]
                );
                break;
        }

        return $forwardTo ?? $this->json(buildError('Unknown operation provided.'));
    }

    /**
     * Retrieve the other members associated with the requesting user's organization.
     *
     *

     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}organizations/members', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function getMembers(Request $request): Response
    {
        $user = $this->authorize($request, $this->getParameter('center_related_acls'), true);
        $members = Users::getUsersAssociatedWithCenter($user->getUserID());

        return $this->json([
            'success' => true,
            'count' => count($members),
            'members' => $members
        ]);
    }

    /**
     *
     * @param Request $request
     * @param string $memberID
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}organizations/members/{memberID}/status', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function getMemberStatus(Request $request, string $memberID): Response
    {
        $user = $this->authorize($request, $this->getParameter('center_related_acls'), true);

        $returnData = [
            'success' => true,
            'message' => '',
            'eligible' => true
        ];

        $member = $this->getValidXDUser($memberID);
        $organization = $user->getOrganizationID();

        // An eligible user must be associated with the currently logged in users center.
        if (!Users::userIsAssociatedWithCenter($memberID, $organization)) {
            throw new BadRequestHttpException('center_mismatch_between_member_and_director');
        }

        // They must not already be a Center Director for the organization.
        if (Centers::hasCenterRelation($memberID, $organization, ROLE_ID_CENTER_DIRECTOR)) {
            $returnData['success'] = false;
            $returnData['message'] = 'is a Center Director';
            return $this->json($returnData);
        }

        // This makes them ineligible for promotion, but eligible for demotion.
        if (Centers::hasCenterRelation($memberID, $organization, ROLE_ID_CENTER_STAFF)) {
            $returnData['eligible'] = false;
        }

        // They must be active
        if (!$member->getAccountStatus()) {
            $returnData['success'] = false;
            $returnData['message'] = 'User is disabled';
            return $this->json($returnData);
        }

        return $this->json($returnData);
    }

    /**
     * @param Request $request
     * @param string $memberID
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}organizations/members/{memberID}/upgrade', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function upgradeMember(Request $request, string $memberID): Response
    {
        try {
            $user = $this->authorize($request, [ROLE_ID_CENTER_DIRECTOR], true);
            $this->logger->error('Successfully Authenticated requesting user has CD');
        } catch (Exception $e) {
            return $this->json(
                [
                    "status" => "not_a_center_director",
                    "success" => false,
                    "totalCount" => 0,
                    "message" => "not_a_center_director",
                    "data" => []
                ]
            );
        }

        $returnData = [];

        // Ensure that the user performing this operation is authorized
        if (!$user->hasAcl(ROLE_ID_CENTER_DIRECTOR) || !$user->getAccountStatus()) {
            return $this->json([
                'success' => false,
                'message' => 'You are not authorized to perform this action'
            ]);
        }

        $member = $this->getValidXDUser($memberID);
        $organization = $user->getActiveOrganization();

        // An eligible user must be associated with the currently logged in users center.
        if (!Users::userIsAssociatedWithCenter($memberID, $organization)) {
            $this->json(\xd_response\buildError('center_mismatch_between_member_and_director'));
        }

        // They must not already be a Center Director for the organization.
        if (Centers::hasCenterRelation($memberID, $organization, ROLE_ID_CENTER_DIRECTOR)) {
            $returnData['success'] = false;
            $returnData['message'] = 'is a Center Director';
            return $this->json($returnData);
        }

        // They must not be a Center Staff for the organization.
        // Although this makes them eligible for demotion.
        if (Centers::hasCenterRelation($memberID, $organization, ROLE_ID_CENTER_STAFF)) {
            $returnData['success'] = false;
            $returnData['message'] = 'is already a Center Staff';
            return $this->json($returnData);
        }

        Users::promoteUserToCenterStaff($member, $organization);
        $returnData['success'] = true;
        $returnData['message'] = "has been upgraded to Center Staff<br />(promoted by {$user->getFormalName()})";

        return $this->json($returnData);
    }

    /**
     * @param Request $request
     * @param string $memberID
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}organizations/members/{memberID}/downgrade', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function downgradeMember(Request $request, ?string $memberID): Response
    {
        try {
            $user = $this->authorize($request, [ROLE_ID_CENTER_DIRECTOR], true);
        } catch (Exception $e) {
            return $this->json(
                [
                    "status" => "not_a_center_director",
                    "success" => false,
                    "totalCount" => 0,
                    "message" => "not_a_center_director",
                    "data" => []
                ]
            );
        }

        $member = $this->getValidXDUser($memberID);
        $organization = $user->getOrganizationID();

        // An eligible user must be associated with the currently logged in users center.
        if (!Users::userIsAssociatedWithCenter($memberID, $organization)) {
            return $this->json(\xd_response\buildError('center_mismatch_between_member_and_director'));
        }

        Users::demoteUserFromCenterStaff($member, $organization);

        return $this->json(['success' => true]);
    }

    /**
     * @param Request $request
     * @return string
     * @throws BadRequestHttpException
     */
    private function getValidMemberIDParameter(Request $request): string
    {
        return $this->getStringParam(
            request: $request,
            name: 'member_id',
            mandatory: true,
            pattern: RESTRICTION_UID
        );
    }

    /**
     * @param string $memberID
     * @return XDUser
     * @throws BadRequestHttpException
     */
    private function getValidXDUser(string $memberID): XDUser
    {
        return XDUser::getUserByID($memberID) ?? throw new BadRequestHttpException('user_does_not_exist');
    }
}
