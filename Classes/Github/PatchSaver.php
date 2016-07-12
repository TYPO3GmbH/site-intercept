<?php
declare(strict_types = 1);

namespace T3G\Intercept\Github;


class PatchSaver
{

    public function getLocalDiff(string $diffUrl)
    {
        $guzzleClient = new Client();
        $response = $guzzleClient->get($diffUrl);
        $diff = (string)$response->getBody();
        $filePath = BASEPATH . '/Patches/' . sha1($diffUrl);
        $patch = fopen($filePath, 'w+');
        fwrite($patch, $diff);
        fclose($patch);
        return $filePath;
    }
}