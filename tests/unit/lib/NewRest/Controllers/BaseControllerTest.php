<?php

namespace UnitTests\NewRest\Controllers;

use Rest\Controllers\BaseControllerProvider;

class BaseControllerTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @dataProvider generateUserDataSet
     * @param array $requestedAcl
     */
    public function testAuthorized(mixed $user, $requestedAcl, $expectedException, $expectedMessage): void
    {
        $attributes = $this->getAttributes($user);
        $request = $this->getRequest($attributes);

        $baseController = $this->getMockForAbstractClass(\Rest\Controllers\BaseControllerProvider::class);
        $exception = null;

        try {
            if ($requestedAcl !== null) {
                $authorized = $baseController->authorize($request, [$requestedAcl]);
            } else {
                $authorized = $baseController->authorize($request);
            }

            $this->assertEquals($authorized, $user);
        } catch (\Exception $e) {
            $exception = $e;
        }

        $exceptionClass = $exception !== null ? $exception::class : null;
        $message = $exception !== null ? $exception->getMessage() : null;

        $this->assertEquals($exceptionClass, $expectedException);
        $this->assertEquals($message, $expectedMessage);

    }

    /**
     * @param $attributes
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    public function getRequest($attributes)
    {
        $mock = $this->createMock(\Symfony\Component\HttpFoundation\Request::class);
        $mock->attributes = $attributes;
        return $mock;
    }


    /**
     * @param $user
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    public function getAttributes($user)
    {
        $builder = $this->getMockBuilder(\Symfony\Component\HttpFoundation\ParameterBag::class);
        $builder->setMethods(['get']);
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
        $mgr = $this->createUser(['mgr', 'usr']);
        $cd = $this->createUser(['cd', 'usr']);
        $pi = $this->createUser(['pi', 'usr']);
        $usr = $this->createUser(['usr'], 'usr');
        $sab = $this->createUser(['usr', 'sab']);
        $pub = $this->createUser(['pub']);

        $accessDeniedException = \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException::class;
        $unauthorizedException = \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException::class;

        $notAuthorized = BaseControllerProvider::EXCEPTION_MESSAGE;

        $tests = [[$mgr, null, null, null], [$mgr, ROLE_ID_MANAGER, null, null], [$cd, null, null, null], [$cd, ROLE_ID_CENTER_DIRECTOR, null, null], [$cd, ROLE_ID_MANAGER, $accessDeniedException, $notAuthorized], [$pi, null, null, null], [$pi, ROLE_ID_PRINCIPAL_INVESTIGATOR, null, null], [$pi, ROLE_ID_USER, null, null], [$pi, ROLE_ID_MANAGER, $accessDeniedException, $notAuthorized], [$pi, ROLE_ID_CENTER_DIRECTOR, $accessDeniedException, $notAuthorized], [$usr, null, null, null], [$usr, ROLE_ID_USER, null, null], [$usr, ROLE_ID_MANAGER, $accessDeniedException, $notAuthorized], [$usr, ROLE_ID_CENTER_DIRECTOR, $accessDeniedException, $notAuthorized], [$usr, ROLE_ID_PRINCIPAL_INVESTIGATOR, $accessDeniedException, $notAuthorized], [$sab, null, null, null], [$sab, 'sab', null, null], [$sab, ROLE_ID_USER, null, null], [$sab, ROLE_ID_MANAGER, $accessDeniedException, $notAuthorized], [$sab, ROLE_ID_CENTER_DIRECTOR, $accessDeniedException, $notAuthorized], [$sab, ROLE_ID_PRINCIPAL_INVESTIGATOR, $accessDeniedException, $notAuthorized], [$pub, null, $unauthorizedException, $notAuthorized], [$pub, ROLE_ID_PUBLIC, null, null], [$pub, ROLE_ID_USER, $unauthorizedException, $notAuthorized], [$pub, ROLE_ID_CENTER_DIRECTOR, $unauthorizedException, $notAuthorized], [$pub, ROLE_ID_MANAGER, $unauthorizedException, $notAuthorized], [$pub, ROLE_ID_PRINCIPAL_INVESTIGATOR, $unauthorizedException, $notAuthorized]];
        return $tests;
    }

    /**
     * Used to create a mock XDUser object suitable for use in both versions of
     * the isAuthorized functions.
     *
     * @param array $roles an array of strings representing the
     *                              roles / acls this user is assigned
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createUser(array $roles)
    {
        $builder = $this->getMockBuilder('\XDUser')
            ->disableOriginalConstructor()
            ->onlyMethods(
                ['getRoles', 'isManager', 'isPublicUser', 'hasAcl', '__toString', 'hasAcls']
            );
        $stub = $builder->getMock();
        $stub->method('getRoles')->willReturn($roles);
        $stub->method('isManager')->willReturnCallback(fn() => in_array(ROLE_ID_MANAGER, $roles));
        $stub->method('isPublicUser')->willReturnCallback(fn() => in_array(ROLE_ID_PUBLIC, $roles));
        $stub->method('hasAcl')->willReturnCallback(function () use ($roles) {
            $args = func_get_args();
            if (count($args) >= 1) {
                $arg = $args[0];
                return in_array($arg, $roles);
            }
            return false;
        });
        $stub->method('__toString')->willReturn(json_encode(['roles' => $roles, 'is_manager' => in_array(ROLE_ID_MANAGER, $roles), 'is_public_user' => in_array(ROLE_ID_PUBLIC, $roles)]));
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
