<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Security;

use App\Security\AuthenticationSuccessHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\HttpUtils;

class AuthenticationSuccessHandlerTest extends TestCase
{
    /**
     * @test
     */
    public function onAuthenticationSuccessAddFlashMessage()
    {
        $flashBackProphecy = $this->prophesize(FlashBag::class);
        $flashBackProphecy->add('success', 'Successfully logged in.')->shouldBeCalled();
        $subject = new AuthenticationSuccessHandler(
            $this->prophesize(HttpUtils::class)->reveal(),
            [],
            $flashBackProphecy->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal()
        );
        $subject->onAuthenticationSuccess(
            $this->prophesize(Request::class)->reveal(),
            $this->prophesize(TokenInterface::class)->reveal()
        );
    }
}
