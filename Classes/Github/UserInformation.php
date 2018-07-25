<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/build-information-service.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Intercept\Github;

use Psr\Http\Message\ResponseInterface;

class UserInformation
{
    public function transformResponse(ResponseInterface $response)
    {
        $userInformation = [
            'email' => 'noreply@example.com'
        ];
        $responseBody = (string)$response->getBody();
        $fullUserInformation = json_decode($responseBody, true);
        $userInformation['user'] = $fullUserInformation['name'] ?: $fullUserInformation['login'];
        if (isset($fullUserInformation['email'])) {
            $userInformation['email'] = $fullUserInformation['email'];
        }
        return $userInformation;
    }
}
