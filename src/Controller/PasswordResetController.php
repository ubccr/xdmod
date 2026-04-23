<?php

declare(strict_types=1);

namespace CCR\Controller;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 *
 */
class PasswordResetController extends BaseController
{
    private static $validModes = ['update', 'create'];

    /**
     *
     * @param Request $request
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route('{prefix}password_reset', requirements: ['prefix' => '.*'], methods: ['GET'])]
    #[Route('/controllers/password_reset.php', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $validationCheck = [
            'status' => INVALID,
            'user_first_name' => 'INVALID',
            'user_id' => INVALID
        ];

        $mode = $this->getStringParam($request, 'mode', false, 'update');
        if (isset($mode) && $mode === 'new') {
            $mode = 'create';
        }

        $rid = $this->getStringParam($request, 'rid', false, null, RESTRICTION_RID);
        if (isset($rid)) {
            $validationCheck = \XDUser::validateRID($rid);
        }


        if ($validationCheck['status'] === INVALID || !in_array($mode, self::$validModes)) {
            return $this->render(
                'twig/password_reset_expired.html.twig',
                [
                    'site_address' => $this->parameters->get('xdmod.portal_settings.general.site_address')
                ]
            );
        }

        return $this->render(
            '/twig/password_reset.html.twig',
            [
                'rid' => $rid,
                'mode' => $mode,
                'first_name' => $validationCheck['user_first_name'],
                'password_max' => CHARLIM_PASSWORD,
                'extjs_path' => '/gui/lib',
                'extjs_version' => '/extjs'
            ]
        );
    }
}
