<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Symfony\Component\HttpFoundation\Request;

return Request::create(
    '/discord/hook/1234test',
    'POST',
    [],
    [],
    [],
    [],
    '{"attachments":[{"color":"danger","text":"<https://bamboo.typo3.com/browse/T3G-DIS-6|T3G \u203a Discord-Webhook-Test \u203a #6> failed. Manual run by <https://bamboo.typo3.com/browse/user/jurian.janssen|Jurian Janssen>","fallback":"T3G \u203a Discord-Webhook-Test \u203a #6 failed. Manual run by Jurian Janssen"}]}'
);
