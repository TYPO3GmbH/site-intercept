<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Twig;

use App\Service\RabbitStatusService;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class InterceptStatusExtension extends AbstractExtension
{
    protected RabbitStatusService $rabbitService;

    public function __construct(
        RabbitStatusService $rabbitService
    ) {
        $this->rabbitService = $rabbitService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'intercept_status',
                [
                    $this, 'render'
                ],
                [
                    'needs_environment' => true,
                    'is_safe' => [ 'html' ]
                ]
            ),
        ];
    }

    public function render(Environment $environment): string
    {
        return $environment->render(
            'extension/interceptStatus.html.twig',
            [
                'rabbitStatus' => $this->rabbitService->getStatus(),
            ]
        );
    }

    public function getName(): string
    {
        return 'intercept_status';
    }
}
