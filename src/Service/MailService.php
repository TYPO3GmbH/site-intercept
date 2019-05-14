<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Extractor\ComposerJson;
use App\Extractor\PushEvent;
use Twig\Environment;

class MailService
{
    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var Environment
     */
    private $templating;

    public function __construct(\Swift_Mailer $mailer, Environment $templating)
    {
        $this->mailer = $mailer;
        $this->templating = $templating;
    }

    /**
     * @param PushEvent $pushEvent
     * @param ComposerJson $composerJson
     * @param string $exceptionMessage
     * @return int
     */
    public function sendMailToAuthorDueToMissingDependency(PushEvent $pushEvent, ComposerJson $composerJson, string $exceptionMessage): int
    {
        $message = $this->createMessageWithTemplate(
            'Documentation rendering failed',
            'email/docs/renderingFailedDueToMissingDependency.html.twig',
            [
                'author' => $composerJson->getFirstAuthor(),
                'package' => $composerJson->getName(),
                'pushEvent' => $pushEvent,
                'reasonPhrase' => $exceptionMessage,
            ]
        );
        $message
            ->setFrom('intercept@typo3.com')
            ->setTo($composerJson->getFirstAuthor()['email'])
        ;

        return $this->send($message);
    }

    /**
     * @param string $subject
     * @return \Swift_Message
     */
    private function createMessage(string $subject): \Swift_Message
    {
        return new \Swift_Message($subject);
    }

    /**
     * @param string $subject
     * @param string $templateFile
     * @param array $templateVariables
     * @return \Swift_Message
     */
    private function createMessageWithTemplate(string $subject, string $templateFile, array $templateVariables): \Swift_Message
    {
        $message = new \Swift_Message($subject);
        $message->setBody(
            $this->templating->render($templateFile, $templateVariables)
        );

        return $message;
    }

    /**
     * @param \Swift_Message $message
     * @return int
     */
    private function send(\Swift_Message $message): int
    {
        return $this->mailer->send($message);
    }
}
