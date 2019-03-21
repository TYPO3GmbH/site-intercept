<?php
declare(strict_types = 1);
namespace App\Tests\Unit\Monolog\Processor;

use App\Monolog\Processor\AddFieldProcessor;
use App\Security\User;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Security\Core\Security;

class AddFieldProcessorTest extends TestCase
{
    /**
     * @test
     */
    public function addFieldProcessorAddsFields()
    {
        $subject = new AddFieldProcessor(['foo' => 'bar']);
        $expected = [
            'extra' => [
                'foo' => 'bar',
            ],
        ];
        $this->assertSame($expected, $subject->__invoke([]));
    }

    /**
     * @test
     */
    public function addFieldProcessorAddsUsernameAndDisplayName()
    {
        /** @var ObjectProphecy|User $user */
        $user = $this->prophesize(User::class);
        /** @var ObjectProphecy|Security $security */
        $security = $this->prophesize(Security::class);
        $security->getUser()->shouldBeCalled()->willReturn($user->reveal());
        $user->getUsername()->shouldBeCalled()->willReturn('myUsername');
        $user->getDisplayName()->shouldBeCalled()->willReturn('myDisplayName');
        $subject = new AddFieldProcessor([ $security->reveal() ]);
        $expected = [
            'extra' => [
                'username' => 'myUsername',
                'userDisplayName' => 'myDisplayName',
            ],
        ];
        $this->assertSame($expected, $subject->__invoke([]));
    }
}
