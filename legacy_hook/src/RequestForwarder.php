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
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ServerRequestInterface;

class RequestForwarder
{
    protected $request;

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    public function to(Uri $target): void
    {
        try {
            $newRequest = new Request(
                $this->request->getMethod(),
                (string)$target,
                $this->request->getHeaders(),
                (string)$this->request->getBody()
            );
            $response = (new Client())->sendAsync($newRequest);
            header('Content-Type:' . implode(chr(10), $response->getHeader('Content-Type')));
            echo (string)$response->getBody();
        } catch (GuzzleException $e) {
            echo $e->getMessage();
        }
    }
}