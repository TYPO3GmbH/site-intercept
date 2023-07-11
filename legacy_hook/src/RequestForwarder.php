<?php
declare(strict_types = 1);

namespace App;

/*
 * This file is part of the package t3g/intercept-legacy-hook.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

readonly class RequestForwarder
{
    public function __construct(private ServerRequestInterface $request)
    {
    }

    public function to(Uri $target): void
    {
        // Unset "Host" header, otherwise request goes to wrong target domain ...
        $headers = $this->request->getHeaders();
        unset($headers['Host']);
        $newRequest = new Request(
            $this->request->getMethod(),
            (string)$target,
            $headers,
            (string)$this->request->getBody()
        );
        $promise = (new Client())->sendAsync($newRequest);
        $promise->then(
            static function (ResponseInterface $response) {
                header('Content-Type:' . implode(chr(10), $response->getHeader('Content-Type')));
                echo (string)$response->getBody();
            },
            static function (RequestException $e) {
                echo $e->getMessage() . "\n";
                echo $e->getRequest()->getMethod();
            }
        )->wait();
    }
}
