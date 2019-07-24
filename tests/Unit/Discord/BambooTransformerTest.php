<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Discord;

use App\Discord\BambooTransformer;
use PHPUnit\Framework\TestCase;

class BambooTransformerTest extends TestCase
{
    private $failedBuildPayload = '{"attachments":[{"color":"danger","text":"<https://bamboo.typo3.com/browse/T3G-DIS-6|T3G \u203a Discord-Webhook-Test \u203a #6> failed. Manual run by <https://bamboo.typo3.com/browse/user/jurian.janssen|Jurian Janssen>","fallback":"T3G \u203a Discord-Webhook-Test \u203a #6 failed. Manual run by Jurian Janssen"}]}';

    private $failedBuildPayloadWithNumbers = '{"attachments":[{"color":"danger","text":"<https://bamboo.typo3.com/browse/T3G-DIS-6|T3G \u203a Discord-Webhook-Test \u203a #6> failed. 4 out of 47 failed. Manual run by <https://bamboo.typo3.com/browse/user/jurian.janssen|Jurian Janssen>","fallback":"T3G \u203a Discord-Webhook-Test \u203a #6 failed. Manual run by Jurian Janssen"}]}';

    private $successBuildPayload = '{"attachments":[{"color":"good","text":"<https://bamboo.typo3.com/browse/T3G-DIS-7|T3G \u203a Discord-Webhook-Test \u203a #7> passed. Manual run by <https://bamboo.typo3.com/browse/user/jurian.janssen|Jurian Janssen>","fallback":"T3G \u203a Discord-Webhook-Test \u203a #7 passed. Manual run by Jurian Janssen"}]}';

    /**
     * @test
     */
    public function transformesFailedBuildCorrectly()
    {
        $transformer = new BambooTransformer();
        $message = $transformer->getDiscordMessage(json_decode($this->failedBuildPayload, true));

        $this->assertEquals(
            '**[T3G › Discord-Webhook-Test › #6](https://bamboo.typo3.com/browse/T3G-DIS-6) failed**' . PHP_EOL . '*Manual run by [Jurian Janssen](https://bamboo.typo3.com/browse/user/jurian.janssen)*',
            $message->getDescription()
        );
        $this->assertEquals(16711680, $message->getColor());
        $this->assertCount(2, $message->getFields());
        $this->assertEquals('#6', $message->getField('Build Identifier')['value']);
        $this->assertEquals('T3G › Discord-Webhook-Test', $message->getField('Buildplan')['value']);
    }

    /**
     * @test
     */
    public function transformesFailedBuildWithNumbersCorrectly()
    {
        $transformer = new BambooTransformer();
        $message = $transformer->getDiscordMessage(json_decode($this->failedBuildPayloadWithNumbers, true));

        $this->assertEquals(
            '**[T3G › Discord-Webhook-Test › #6](https://bamboo.typo3.com/browse/T3G-DIS-6) failed. 4 out of 47 failed.**' . PHP_EOL . '*Manual run by [Jurian Janssen](https://bamboo.typo3.com/browse/user/jurian.janssen)*',
            $message->getDescription()
        );
        $this->assertEquals(16711680, $message->getColor());
        $this->assertCount(2, $message->getFields());
        $this->assertEquals('#6', $message->getField('Build Identifier')['value']);
        $this->assertEquals('T3G › Discord-Webhook-Test', $message->getField('Buildplan')['value']);
    }

    /**
     * @test
     */
    public function transformesSuccessBuildCorrectly()
    {
        $transformer = new BambooTransformer();
        $message = $transformer->getDiscordMessage(json_decode($this->successBuildPayload, true));

        $this->assertEquals(
            '**[T3G › Discord-Webhook-Test › #7](https://bamboo.typo3.com/browse/T3G-DIS-7) passed**' . PHP_EOL . '*Manual run by [Jurian Janssen](https://bamboo.typo3.com/browse/user/jurian.janssen)*',
            $message->getDescription()
        );
        $this->assertEquals(65280, $message->getColor());
        $this->assertCount(2, $message->getFields());
        $this->assertEquals('#7', $message->getField('Build Identifier')['value']);
        $this->assertEquals('T3G › Discord-Webhook-Test', $message->getField('Buildplan')['value']);
    }
}
