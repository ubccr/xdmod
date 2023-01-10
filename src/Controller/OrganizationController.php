<?php

declare(strict_types=1);

namespace Access\Controller;

use Exception;
use Models\Services\Centers;
use Models\Services\Users;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
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
     * @Route("/controllers/role_manager.php")
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function index(Request $request): Response
    {
        $operation = $this->getStringParam($request, 'operation', true);
        $memberId = $this->getStringParam($request, 'member_id');
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
     * @Route("/organizations/members", methods={"POST"}) 

     * @param Request $request
     * @return Response
     * @throws Exception
     */
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
     * @Route("/organizations/members/{memberId}/status", methods={"POST"}) 

     * @param Request $request
     * @param string $memberId
     * @return Response
     * @throws Exception
     */
    public function getMemberStatus(Request $request, string $memberId): Response
    {
        $user = $this->authorize($request, $this->getParameter('center_related_acls'), true);
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
     * @Route("/organizations/members/{memberId}/upgrade", methods={"POST"}) 

     * @param Request $request
     * @param string $memberId
     * @return Response
     * @throws Exception
     */
    public function upgradeMember(Request $request, string $memberId): Response
    {
        $user = $this->authorize($request, $this->getParameter('center_related_acls'), true);
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
     * @Route("/organizations/members/{memberId}/downgrade", methods={"POST"}) 

     * @param Request $request
     * @param string $memberId
     * @return Response
     * @throws Exception
     */
    public function downgradeMember(Request $request, string $memberId): Response
    {
        $user = $this->authorize($request, $this->getParameter('center_related_acls'), true);
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