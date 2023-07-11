<?php

declare(strict_types=1);

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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

readonly class MailService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
    ) {
    }

    public function sendMailToAuthorDueToMissingDependency(PushEvent $pushEvent, ComposerJson $composerJson, string $exceptionMessage): void
    {
        try {
            $author = $composerJson->getFirstAuthor();
            $email = $this->createMessageWithTemplate(
                'email/docs/renderingFailedDueToMissingDependency.html.twig',
                [
                    'author' => $author,
                    'package' => $composerJson->getName(),
                    'pushEvent' => $pushEvent,
                    'reasonPhrase' => $exceptionMessage,
                ]
            );
            if (!empty($author['email'])) {
                $email = $email
                    ->from('intercept@typo3.com')
                    ->to($composerJson->getFirstAuthor()['email']);
            } else {
                return;
            }
        } catch (DocsComposerMissingValueException) {
            // Thrown if author is not set, we can't send a mail, then.
            return;
        }

        $this->mailer->send($email);
    }

    private function createMessageWithTemplate(string $templateFile, array $templateVariables): Email
    {
        $template = $this->twig->load($templateFile);
        $subject = $template->renderBlock('subject', $templateVariables);
        $html = $template->renderBlock('body_html', $templateVariables);
        $text = $template->renderBlock('body_text', $templateVariables);
        $text = implode("\n", array_map(static fn ($item) => trim($item), explode("\n", $text)));

        $message = new Email();
        $message
            ->subject($subject)
            ->text($text)
            ->html($html);

        return $message;
    }
}
