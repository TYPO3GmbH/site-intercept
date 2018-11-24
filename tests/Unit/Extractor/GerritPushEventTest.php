<?php
declare(strict_types = 1);
namespace App\Tests\Unit\Extractor;

use App\Exception\DoNotCareException;
use App\Extractor\GerritPushEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class GerritPushEventTest extends TestCase
{
    /**
     * @test
     */
    public function constructorExtractsValues()
    {
        $request = $this->prophesize(Request::class);
        $post = $this->prophesize(ParameterBag::class);
        $request->request = $post->reveal();
        $post->get('changeUrl')->willReturn('https://review.typo3.org/48574/');
        $post->get('patchset')->willReturn('42');
        $post->get('branch')->willReturn('master');
        $subject = new GerritPushEvent($request->reveal());
        $this->assertSame('https://review.typo3.org/48574/', $subject->changeUrl);
        $this->assertSame(42, $subject->patchSet);
        $this->assertSame('master', $subject->branch);
    }

    /**
     * @test
     */
    public function constructorThrowsWithWrongBranch()
    {
        $this->expectException(DoNotCareException::class);
        $request = $this->prophesize(Request::class);
        $post = $this->prophesize(ParameterBag::class);
        $request->request = $post->reveal();
        $post->get('changeUrl')->willReturn('https://review.typo3.org/48574/');
        $post->get('patchset')->willReturn('42');
        $post->get('branch')->willReturn('some-other-branch');
        new GerritPushEvent($request->reveal());
    }

    /**
     * @test
     */
    public function constructorThrowsWithWrongEmptyDetails()
    {
        $this->expectException(\RuntimeException::class);
        $request = $this->prophesize(Request::class);
        $post = $this->prophesize(ParameterBag::class);
        $request->request = $post->reveal();
        $post->get('changeUrl')->willReturn('https://review.typo3.org/48574/');
        $post->get('patchset')->willReturn('');
        $post->get('branch')->willReturn('master');
        new GerritPushEvent($request->reveal());
    }
}
