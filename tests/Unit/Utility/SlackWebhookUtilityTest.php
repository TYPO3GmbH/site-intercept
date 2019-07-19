<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Utility;

use App\Utility\SlackWebhookUtility;
use PHPUnit\Framework\TestCase;

class SlackWebhookUtilityTest extends TestCase
{
    public function textDataProvider(): array
    {
        return [
            ['<https://bamboo.typo3.com/browse/T3G-DIS-6|T3G › Discord-Webhook-Test › #6>', '[T3G › Discord-Webhook-Test › #6](https://bamboo.typo3.com/browse/T3G-DIS-6)'],
            ['<https://bamboo.typo3.com/browse/T3G-DIS-6|T3G › Discord-Webhook-Test › #6> failed. 4 out of 47 failed. Manual run by <https://bamboo.typo3.com/browse/user/jurian.janssen|Jurian Janssen>', '[T3G › Discord-Webhook-Test › #6](https://bamboo.typo3.com/browse/T3G-DIS-6) failed. 4 out of 47 failed. Manual run by [Jurian Janssen](https://bamboo.typo3.com/browse/user/jurian.janssen)']
        ];
    }

    /**
     * @test
     * @dataProvider textDataProvider
     * @param string $input
     * @param string $expected
     */
    public function transformsUrlsCorrectly(string $input, string $expected): void
    {
        $this->assertSame($expected, SlackWebhookUtility::transformUrls($input));
    }
}
