<?php

namespace NewRest\Utilities;

use NewRest\Controllers\BaseControllerProvider;

class BaseControllerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var array of mock XDUser classes
     */
    private $users;

    /**
     * @var array  of acls to use during the authorization attempts.
     */
    private $requestedAcls;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->users = array(
            $this->createUser(array(STATUS_MANAGER_ROLE, 'usr'), STATUS_MANAGER_ROLE),
            $this->createUser(array('cd', 'usr'), 'cd'),
            $this->createUser(array('pi', 'usr'), 'pi'),
            $this->createUser(array('usr'), 'usr'),
            $this->createUser(array('usr', SAB_MEMBER), SAB_MEMBER),
            $this->createUser(array('pub'), 'pub')
        );

        $this->requestedAcls = array(
            array(STATUS_MANAGER_ROLE),
            array('cd'),
            array('pi'),
            array('usr'),
            array(SAB_MEMBER)
        );
    }

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

        $defaultMessage = Authorization::_DEFAULT_MESSAGE;
        $notASABMember = $defaultMessage . "\n[ Not a SAB Member ]";
        $notAManager = $defaultMessage . "\n[ Not a Manager ]";
        $notACenterDirector = $defaultMessage . "\n [ Not a Center Director ]";
        $notAuthorized = $defaultMessage . " [ Not Authorized ]";

        $tests = array(
            array($mgr, null, null, null),
            array($mgr, ROLE_ID_MANAGER, null, null),
            array($mgr, STATUS_CENTER_DIRECTOR_ROLE, $accessDeniedException, $notACenterDirector),
            array($mgr, SAB_MEMBER, null, null),
            // This should not be the expected behavior
            array($mgr, STATUS_MANAGER_ROLE, $accessDeniedException, $notAuthorized),

            array($cd, null, null, null),
            array($cd, ROLE_ID_MANAGER, $accessDeniedException, $notAuthorized),
            // This should not be the expected behavior
            array($cd, STATUS_CENTER_DIRECTOR_ROLE, $accessDeniedException, $notAuthorized),
            array($cd, STATUS_MANAGER_ROLE, $accessDeniedException, $notAManager),
            array($cd, SAB_MEMBER, $accessDeniedException, $notASABMember),

            array($pi, null, null, null),
            array($pi, 'pi', null, null),
            array($pi, STATUS_CENTER_DIRECTOR_ROLE, $accessDeniedException, $notACenterDirector),
            array($pi, ROLE_ID_MANAGER, $accessDeniedException, $notAuthorized),
            array($pi, STATUS_MANAGER_ROLE, $accessDeniedException, $notAManager),
            array($pi, SAB_MEMBER, $accessDeniedException, $notASABMember),

            array($usr, null, null, null),
            array($usr, 'usr', null, null),
            array($usr, STATUS_CENTER_DIRECTOR_ROLE, $accessDeniedException, $notACenterDirector),
            array($usr, ROLE_ID_MANAGER, $accessDeniedException, $notAuthorized),
            array($usr, STATUS_MANAGER_ROLE, $accessDeniedException, $notAManager),
            array($usr, SAB_MEMBER, $accessDeniedException, $notASABMember),

            array($sab, null, null, null),
            array($sab, 'sab', null, null),
            array($sab, STATUS_CENTER_DIRECTOR_ROLE, $accessDeniedException, $notACenterDirector),
            array($sab, ROLE_ID_MANAGER, $accessDeniedException, $notAuthorized),
            array($sab, STATUS_MANAGER_ROLE, $accessDeniedException, $notAManager),
            // This should not be the expected behavior
            array($sab, SAB_MEMBER, $accessDeniedException, $notAuthorized),

            array($pub, null, $unauthorizedException, $notAuthorized),
            array($pub, STATUS_CENTER_DIRECTOR_ROLE, $unauthorizedException, $notACenterDirector),
            array($pub, ROLE_ID_MANAGER, $unauthorizedException, $notAuthorized),
            array($pub, STATUS_MANAGER_ROLE, $unauthorizedException, $notAManager),
            array($pub, SAB_MEMBER, $unauthorizedException, $notASABMember)
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
