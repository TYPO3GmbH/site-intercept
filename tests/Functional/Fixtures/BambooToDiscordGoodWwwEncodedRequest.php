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
    'payload=%7B%22attachments%22%3A%5B%7B%22color%22%3A%22good%22%2C%22text%22%3A%22%3Chttps%3A%2F%2Fbamboo.typo3.com%2Fbrowse%2FT3G-DIS-9%7CT3G+%5Cu203a+Discord-Webhook-Test+%5Cu203a+%239%3E+passed.+Manual+run+by+%3Chttps%3A%2F%2Fbamboo.typo3.com%2Fbrowse%2Fuser%2Fjurian.janssen%7CJurian+Janssen%3E%22%2C%22fallback%22%3A%22T3G+%5Cu203a+Discord-Webhook-Test+%5Cu203a+%239+passed.+Manual+run+by+Jurian+Janssen%22%7D%5D%7D'
);
