<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use GuzzleHttp\Psr7\Response;

return new Response(
    200,
    [],
    ')]}\'
{
  "/COMMIT_MSG": {
    "status": "A",
    "lines_inserted": 11,
    "size_delta": 399,
    "size": 399
  },
  "typo3/sysext/redirects/Classes/Hooks/bla.php": {
    "status": "A",
    "lines_inserted": 43,
    "size_delta": 1647,
    "size": 1647
  },
  "typo3/sysext/redirects/ext_localconf.php": {
    "lines_inserted": 1,
    "size_delta": 221,
    "size": 1381
  }
}'
);
