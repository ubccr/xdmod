<?php

namespace CCR\Controller\InternalDashboard;

use CCR\Controller\BaseController;
use Exception;
use Models\Services\Acls;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use XDWarehouse;

class SABUserController extends BaseController
{
    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('/controllers/sab_user.php')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->authorize($request, ['mgr']);

        $operation = $this->getStringParam($request, 'operation', true);
        switch ($operation) {
            case 'enum_tg_users':
                return $this->enumTgUsers($request);
            case 'assign_assumed_person':
            case 'get_mapping':
                /* these operations are not currently used. */
                break;
        }
        return $this->json(['success' => false, 'message' => 'invalid operation specified']);
    }

    /**
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    private function enumTgUsers(Request $request): Response
    {
        $start = $this->getIntParam($request, 'start', true);
        $limit = $this->getIntParam($request, 'limit');
        $searchMode = $this->getStringParam($request, 'search_mode', true, null, RESTRICTION_SEARCH_MODE);
        $piOnly = $this->getStringParam($request, 'pi_only', true, null, RESTRICTION_YES_NO);
        $usePiFilter = $piOnly === 'y';

        $query = $this->getStringParam($request, 'query');
        $userManagement = $this->getStringParam($request, 'userManagement');

        $user = $this->getXDUser($request->getSession());

        $universityId = null;
        if ($user->hasAcl(ROLE_ID_CAMPUS_CHAMPION) && !isset($userManagement)) {
            $universityId = Acls::getDescriptorParamValue($user, ROLE_ID_CAMPUS_CHAMPION, 'provider');
        }

        $searchMethod = null;
        if ($searchMode === 'formal_name') {
            $searchMethod = FORMAL_NAME_SEARCH;
        } elseif ($searchMode === 'username') {
            $searchMethod = USERNAME_SEARCH;
        }
        $xdw = new XDWarehouse();
        list($userCount, $users) = $xdw->enumerateGridUsers(
            $searchMethod,
            $start,
            $limit,
            $query,
            $usePiFilter,
            $universityId
        );

        $entry_id = 0;

        $userEntries = [];
        foreach ($users as $currentUser) {
            $entry_id++;

            if ($searchMethod == FORMAL_NAME_SEARCH) {
                $personName = $currentUser['long_name'];
                $personID = $currentUser['id'];
            } elseif ($searchMethod == USERNAME_SEARCH) {
                $personName = $currentUser['absusername'];

                // Append the absusername to the id so that each entry is guaranteed
                // to have a unique identifier (needed for dependent ExtJS combobox
                // (TGUserDropDown.js) to work properly regarding selections).
                $personID = $currentUser['id'] . ';' . $currentUser['absusername'];
            }

            $userEntries[] = [
                'id' => $entry_id,
                'person_id' => $personID,
                'person_name' => $personName
            ];
        }

        $data = [
            'success' => true,
            'status' => 'success',
            'message' => 'success',
            'total_user_count' => $userCount,
            'users' => $userEntries,
        ];
        return $this->json($data);
    }
}
