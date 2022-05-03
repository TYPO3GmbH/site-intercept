<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Exception\Composer\DocsComposerMissingValueException;
use App\Extractor\ComposerJson;
use App\Extractor\PushEvent;
use Swift_Mailer;
use Swift_Message;
use Twig\Environment;

class MailService
{
    private Swift_Mailer $mailer;

    private Environment $templating;

    public function __construct(Swift_Mailer $mailer, Environment $templating)
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
    public function sendMailToAuthorDueToFailedRendering(PushEvent $pushEvent, ComposerJson $composerJson, string $exceptionMessage): int
    {
        try {
            $author = $composerJson->getFirstAuthor();
            $message = $this->createMessageWithTemplate(
                'Documentation rendering failed',
                'email/docs/renderingFailed.html.twig',
                [
                    'author' => $author,
                    'package' => $composerJson->getName(),
                    'pushEvent' => $pushEvent,
                    'reasonPhrase' => $exceptionMessage,
                ]
            );
            if (!empty($author['email'])) {
                $message
                    ->setFrom('intercept@typo3.com')
                    ->setTo($composerJson->getFirstAuthor()['email']);
            } else {
                return 0;
            }
        } catch (DocsComposerMissingValueException $e) {
            // Thrown if author is not set, we can't send a mail, then.
            return 0;
        }

        return $this->send($message);
    }

    /**
     * @param string $subject
     * @param string $templateFile
     * @param array $templateVariables
     * @return Swift_Message
     */
    private function createMessageWithTemplate(string $subject, string $templateFile, array $templateVariables): Swift_Message
    {
        $message = new Swift_Message($subject);
        $message->setBody($this->templating->render($templateFile, $templateVariables), 'text/html');

        return $message;
    }

    /**
     * @param Swift_Message $message
     * @return int
     */
    private function send(Swift_Message $message): int
    {
        return $this->mailer->send($message);
    }
}
