<?php
declare(strict_types = 1);
namespace App\Tests\Unit\Security;

use App\Security\AuthenticationSuccessHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\HttpUtils;

class AuthenticationSuccessHandlerTest extends TestCase
{
    /**
     * @test
     */
    public function onAuthenticationSuccessAddFlashMessage() {
        $flashBackProphecy = $this->prophesize(FlashBag::class);
        $flashBackProphecy->add('success', 'Successfully logged in.')->shouldBeCalled();
        $subject = new AuthenticationSuccessHandler(
            $this->prophesize(HttpUtils::class)->reveal(),
            [],
            $flashBackProphecy->reveal()
        );
        $subject->onAuthenticationSuccess(
            $this->prophesize(Request::class)->reveal(),
            $this->prophesize(TokenInterface::class)->reveal()
        );
    }
}
