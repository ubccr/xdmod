<?php

namespace NewRest\Utilities;

use NewRest\Controllers\BaseControllerProvider;

class BaseControllerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider generateUserDataSet
     * @param mixed $user
     * @param array $requestedAcl
     */
    public function testAuthorized($user, $requestedAcl, $expectedException, $expectedMessage)
    {
        $attributes = $this->getAttributes($user);
        $request = $this->getRequest($attributes);

        $baseController = $this->getMockForAbstractClass('NewRest\Controllers\BaseControllerProvider');
        $exception = null;

        try {
            if ($requestedAcl !== null) {
                $authorized = $baseController->authorize($request, array($requestedAcl));
            } else {
                $authorized = $baseController->authorize($request);
            }

            $this->assertEquals($authorized, $user);
        } catch (\Exception $e) {
            $exception = $e;
        }

        $exceptionClass = $exception !== null ? get_class($exception) : null;
        $message = $exception !== null ? $exception->getMessage() : null;

        $this->assertEquals($exceptionClass, $expectedException);
        $this->assertEquals($message, $expectedMessage);

    }

    /**
     * @param $attributes
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    public function getRequest($attributes)
    {
        $mock = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $mock->attributes = $attributes;
        return $mock;
    }


    /**
     * @param $user
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    public function getAttributes($user)
    {
        $builder = $this->getMockBuilder('\Symfony\Component\HttpFoundation\ParameterBag');
        $builder->setMethods(array('get'));
        $mock = $builder->getMock();
        $mock->method('get')
            ->with($this->equalTo(BaseControllerProvider::_USER))
            ->willReturn($user);
        return $mock;
    }


    /**
     * The Data Provider for the before / after tests.
     *
     * @return array in the form of:
     * array(
     *     array(
     *         mockUser,
     *         array('requested', 'acls')
     *     ),
     *     ...
     * )
     */
    public function generateUserDataSet()
    {
        $mgr = $this->createUser(array('mgr', 'usr'), 'mgr');
        $cd = $this->createUser(array('cd', 'usr'), 'cd');
        $pi = $this->createUser(array('pi', 'usr'), 'pi');
        $usr = $this->createUser(array('usr'), 'usr');
        $sab = $this->createUser(array('usr', 'sab'), 'sab');
        $pub = $this->createUser(array('pub'), 'pub');

        $accessDeniedException = 'Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException';
        $unauthorizedException = 'Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException';

        $notAuthorized = BaseControllerProvider::EXCEPTION_MESSAGE;

        $tests = array(
            array($mgr, null, null, null),
            array($mgr, ROLE_ID_MANAGER, null, null),

            array($cd, null, null, null),
            array($cd, ROLE_ID_CENTER_DIRECTOR, null, null),
            array($cd, ROLE_ID_MANAGER, $accessDeniedException, $notAuthorized),

            array($pi, null, null, null),
            array($pi, ROLE_ID_PRINCIPAL_INVESTIGATOR, null, null),
            array($pi, ROLE_ID_USER, null, null),
            array($pi, ROLE_ID_MANAGER, $accessDeniedException, $notAuthorized),
            array($pi, ROLE_ID_CENTER_DIRECTOR, $accessDeniedException, $notAuthorized),

            array($usr, null, null, null),
            array($usr, ROLE_ID_USER, null, null),
            array($usr, ROLE_ID_MANAGER, $accessDeniedException, $notAuthorized),
            array($usr, ROLE_ID_CENTER_DIRECTOR, $accessDeniedException, $notAuthorized),
            array($usr, ROLE_ID_PRINCIPAL_INVESTIGATOR, $accessDeniedException, $notAuthorized),

            array($sab, null, null, null),
            array($sab, 'sab', null, null),
            array($sab, ROLE_ID_USER, null, null),
            array($sab, ROLE_ID_MANAGER, $accessDeniedException, $notAuthorized),
            array($sab, ROLE_ID_CENTER_DIRECTOR, $accessDeniedException, $notAuthorized),
            array($sab, ROLE_ID_PRINCIPAL_INVESTIGATOR, $accessDeniedException, $notAuthorized),

            array($pub, null, $unauthorizedException, $notAuthorized),
            array($pub, ROLE_ID_PUBLIC, null, null),
            array($pub, ROLE_ID_USER, $unauthorizedException, $notAuthorized),
            array($pub, ROLE_ID_CENTER_DIRECTOR, $unauthorizedException, $notAuthorized),
            array($pub, ROLE_ID_MANAGER, $unauthorizedException, $notAuthorized),
            array($pub, ROLE_ID_PRINCIPAL_INVESTIGATOR, $unauthorizedException, $notAuthorized)

        );
        return $tests;
    }

    /**
     * Used to create a mock XDUser object suitable for use in both versions of
     * the isAuthorized functions.
     *
     * @param array $roles an array of strings representing the
     *                              roles / acls this user is assigned
     * @param string $activeRole this is used by the old version of
     *                              isAuthorized only and represents the users
     *                              currently active role.
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createUser(array $roles, $activeRole)
    {
        $activeRoleBuilder = $this->getMockBuilder('\User\aRole')
            ->disableOriginalConstructor()
            ->setMethods(array('getIdentifier'));
        $mockActiveRole = $activeRoleBuilder->getMockForAbstractClass();
        $mockActiveRole->method('getIdentifier')->willReturn($activeRole);

        $builder = $this->getMockBuilder('\XDUser')
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'getRoles',
                    'isManager',
                    'isPublicUser',
                    'getActiveRole',
                    'hasAcl',
                    '__toString'
                )
            );
        $stub = $builder->getMock();
        $stub->method('getRoles')->willReturn($roles);
        $stub->method('isManager')->willReturnCallback(function () use ($roles) {
            return in_array(ROLE_ID_MANAGER, $roles);
        });
        $stub->method('isPublicUser')->willReturnCallback(function () use ($roles) {
            return in_array(ROLE_ID_PUBLIC, $roles);
        });
        $stub->method('getActiveRole')->willReturn($mockActiveRole);
        $stub->method('hasAcl')->willReturnCallback(function () use ($roles) {
            $args = func_get_args();
            if (count($args) >= 1) {
                $arg = $args[0];
                return in_array($arg, $roles);
            }
            return false;
        });
        $stub->method('__toString')->willReturn(json_encode(array(
            'roles' => $roles,
            'is_manager' => in_array(ROLE_ID_MANAGER, $roles),
            'is_public_user' => in_array(ROLE_ID_PUBLIC, $roles),
            'active_role' => $activeRole
        )));
        $stub->method('hasAcls')->willreturnCallback(
            function () use ($roles) {
                $args = func_get_args();
                if (count($args) >= 1 && is_array($args[0])) {
                    $requested = $args[0];
                    $total = 0;
                    foreach ($requested as $value) {
                        $found = in_array($value, $roles);
                        $total += $found === true
                            ? 1
                            : 0;
                    }
                    return $total === count($requested);
                }
                return false;
            }
        );
        return $stub;
    }
}
