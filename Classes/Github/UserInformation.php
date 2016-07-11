<?php
declare(strict_types = 1);

namespace T3G\Intercept\Github;

use Psr\Http\Message\ResponseInterface;

class UserInformation
{
    public function transform(ResponseInterface $response)
    {
        $userInformation = [
            'email' => 'noreply@typo3.org'
        ];
        $responseBody = (string)$response->getBody();
        $fullUserInformation = json_decode($responseBody, true);
        $userInformation['user'] = $fullUserInformation['login'];
        if (isset($fullUserInformation['email'])) {
            $userInformation['email'] = $fullUserInformation['email'];
        }
        return $userInformation;
    }
}