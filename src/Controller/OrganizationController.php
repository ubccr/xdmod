<?php

declare(strict_types=1);

namespace Access\Controller;

use Exception;
use Models\Services\Centers;
use Models\Services\Users;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        $operation = $this->getStringParam($request, 'operation', true);
        # Note: this is here so that we get the same error messages for the same tests as previously.
        # Once we deprecate the old routes this should go away.
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

        try {
            $memberId = $this->getStringParam($request, 'member_id',false, null, RESTRICTION_UID );
        } catch (Exception $e) {
            return $this->json(buildError("Invalid value specified for 'member_id'."));
        }

        if (is_null($memberId)) {
            return $this->json(buildError("'member_id' not specified."));
        }

        switch($operation) {
            case 'downgrade_member':
                return $this->downgradeMember($request, $memberId);
            case 'enum_center_staff_members':
                return $this->getMembers($request);
            case 'get_member_status':
                return $this->getMemberStatus($request, $memberId);
            case 'upgrade_member':
                return $this->upgradeMember($request, $memberId);
        }

        return $this->json(buildError('Unknown operation provided.'));

    }

    /**
     * Retrieve the other members associated with the requesting user's organization.
     *
     *

     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/organizations/members', requirements: ['prefix' => '.*'], methods: ['POST'])]
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
     * @param string $memberId
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/organizations/members/{memberId}/status', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function getMemberStatus(Request $request, string $memberId): Response
    {
        $user = $this->authorize($request, $this->getParameter('center_related_acls'), true);

        if (empty($memberId)) {
            return $this->json(buildError("Invalid value specified for 'member_id'."));
        }

        $member = XDUser::getUserByID($memberId);
        if ($member === null) {
            return $this->json(\xd_response\buildError('user_does_not_exist'));
        }

        $returnData = [
            'success' => true,
            'message' => '',
            'eligible' => true
        ];

        $organization = $user->getOrganizationID();
        $memberUserId = $member->getUserID();

        // An eligible user must be associated with the currently logged in users center.
        if (!Users::userIsAssociatedWithCenter($memberUserId, $organization)) {
            throw new BadRequestHttpException('center_mismatch_between_member_and_director');
        }

        // They must not already be a Center Director for the organization.
        if (Centers::hasCenterRelation($memberUserId, $organization, ROLE_ID_CENTER_DIRECTOR)) {
            $returnData['success'] = false;
            $returnData['message'] = 'is a Center Director';
            return $this->json($returnData);
        }

        // This makes them ineligible for promotion, but eligible for demotion.
        if (Centers::hasCenterRelation($memberUserId, $organization, ROLE_ID_CENTER_STAFF)) {
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
     * @param string $memberId
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/organizations/members/{memberId}/upgrade', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function upgradeMember(Request $request, string $memberId): Response
    {
        $this->logger->error('Upgrading Member Id: ' . var_export($memberId, true));
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
        $this->logger->error('Checking member id next.');
        if (empty($memberId)) {
            return $this->json(buildError("Invalid value specified for 'member_id'."));
        }
        $member = XDUser::getUserByID($memberId);
        if ($member === null) {
            return $this->json(\xd_response\buildError('user_does_not_exist'));
        }
        $returnData = [];

        // Ensure that the user performing this operation is authorized
        if (!$user->hasAcl(ROLE_ID_CENTER_DIRECTOR) || !$user->getAccountStatus()) {
            return $this->json([
                'success' => false,
                'message' => 'You are not authorized to perform this action'
            ]);
        }
        $organization = $user->getActiveOrganization();
        $memberUserId = $member->getUserID();

        // An eligible user must be associated with the currently logged in users center.
        if (!Users::userIsAssociatedWithCenter($memberUserId, $organization)) {
            $this->json(\xd_response\buildError('center_mismatch_between_member_and_director'));
        }

        // They must not already be a Center Director for the organization.
        if (Centers::hasCenterRelation($memberUserId, $organization, ROLE_ID_CENTER_DIRECTOR)) {
            $returnData['success'] = false;
            $returnData['message'] = 'is a Center Director';
            return $this->json($returnData);
        }

        // They must not be a Center Staff for the organization.
        // Although this makes them eligible for demotion.
        if (Centers::hasCenterRelation($memberUserId, $organization, ROLE_ID_CENTER_STAFF)) {
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
     * @param ?string $memberId
     * @return Response
     * @throws Exception
     */
    #[Route('{prefix}/organizations/members/{memberId}/downgrade', requirements: ['prefix' => '.*'], methods: ['POST'])]
    public function downgradeMember(Request $request, ?string $memberId): Response
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

        if (empty($memberId)) {
            return $this->json(buildError("Invalid value specified for 'member_id'."));
        }

        try {
            $memberId = $this->getStringParam($request, 'member_id', false, null, RESTRICTION_UID);
        } catch (Exception $e) {
            return $this->json(buildError("Invalid value specified for 'member_id'."));
        }

        $member = XDUser::getUserByID($memberId);
        if ($member === null) {
            return $this->json(\xd_response\buildError('user_does_not_exist'));
        }

        $organization = $user->getOrganizationID();
        $memberUserId = $member->getUserID();

        // An eligible user must be associated with the currently logged in users center.
        if (!Users::userIsAssociatedWithCenter($memberUserId, $organization)) {
            return $this->json(\xd_response\buildError('center_mismatch_between_member_and_director'));
        }

        Users::demoteUserFromCenterStaff($member, $organization);

        return $this->json(['success' => true]);
    }

}
