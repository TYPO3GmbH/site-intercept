<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/build-information-service.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Intercept\Github;

/**
 * Class PatchSaver
 *
 * @codeCoverageIgnore tested via integration tests only
 */
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
