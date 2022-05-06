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
     * @param string $warningMessage
     * @return int
     */
    public function sendMailToAuthorsDueToRenderingWarn(PushEvent $pushEvent, ComposerJson $composerJson, string $warningMessage): int
    {
        try {
            $authors = $composerJson->getAuthorsHavingEmailAddress();
            if (count($authors)) {
                $firstAuthor = array_shift($authors);
                $message = $this->createMessageWithTemplate(
                    'Documentation rendering warning',
                    'email/docs/renderingWarn.html.twig',
                    'email/docs/renderingWarn.txt.twig',
                    [
                        'author' => $firstAuthor,
                        'package' => $composerJson->getName(),
                        'pushEvent' => $pushEvent,
                        'reasonPhrase' => $warningMessage,
                    ]
                );
                $message
                    ->setFrom('intercept@typo3.com')
                    ->setTo($firstAuthor['email'])
                    ->setCc(array_map(function ($author) {
                        return $author['email'];
                    }, $authors));
            } else {
                return 0;
            }
        } catch (DocsComposerMissingValueException $e) {
            // Thrown if authors are not set, we can't send a mail, then.
            return 0;
        }

        return $this->send($message);
    }

    /**
     * @param PushEvent $pushEvent
     * @param ComposerJson $composerJson
     * @param string $exceptionMessage
     * @return int
     */
    public function sendMailToAuthorsDueToRenderingFail(PushEvent $pushEvent, ComposerJson $composerJson, string $exceptionMessage): int
    {
        try {
            $authors = $composerJson->getAuthorsHavingEmailAddress();
            if (count($authors)) {
                $firstAuthor = array_shift($authors);
                $message = $this->createMessageWithTemplate(
                    'Documentation rendering failed',
                    'email/docs/renderingFail.html.twig',
                    'email/docs/renderingFail.txt.twig',
                    [
                        'author' => $firstAuthor,
                        'package' => $composerJson->getName(),
                        'pushEvent' => $pushEvent,
                        'reasonPhrase' => $exceptionMessage,
                    ]
                );
                $message
                    ->setFrom('intercept@typo3.com')
                    ->setTo($firstAuthor['email'])
                    ->setCc(array_map(function ($author) {
                        return $author['email'];
                    }, $authors));
            } else {
                return 0;
            }
        } catch (DocsComposerMissingValueException $e) {
            // Thrown if authors are not set, we can't send a mail, then.
            return 0;
        }

        return $this->send($message);
    }

    /**
     * @param string $subject
     * @param string $htmlTemplateFile
     * @param string $plainTemplateFile
     * @param array $templateVariables
     * @return Swift_Message
     */
    private function createMessageWithTemplate(string $subject, string $htmlTemplateFile, string $plainTemplateFile, array $templateVariables): Swift_Message
    {
        $message = new Swift_Message($subject);
        $message->setBody($this->templating->render($htmlTemplateFile, $templateVariables), 'text/html');
        $message->addPart($this->templating->render($plainTemplateFile, $templateVariables), 'text/plain');

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
