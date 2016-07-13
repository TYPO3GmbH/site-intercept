<?php
declare(strict_types = 1);

namespace T3G\Intercept\Tests\Unit\Github;

use Psr\Http\Message\ResponseInterface;
use T3G\Intercept\Github\UserInformation;

class UserInformationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @return void
     */
    public function transformReturnsUserNameAndEmailAddressIfAvailable()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getBody()->willReturn(file_get_contents(BASEPATH . '/Tests/Fixtures/GithubUserInformation.json'));
        $userInformation = new UserInformation();
        $info = $userInformation->transformResponse($response->reveal());
        self::assertSame('psychomieze', $info['user']);
        self::assertSame('susanne.moog@gmail.com', $info['email']);
    }

    /**
     * @test
     * @return void
     */
    public function transformUsesNameIfAvailable()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getBody()->willReturn(file_get_contents(BASEPATH . '/Tests/Fixtures/GithubUserInformationWithNameWithoutEmail.json'));
        $userInformation = new UserInformation();
        $info = $userInformation->transformResponse($response->reveal());
        self::assertSame('Susanne Moog', $info['user']);
        self::assertSame('noreply@example.com', $info['email']);
    }
}
