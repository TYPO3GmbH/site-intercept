<?php

declare(strict_types=1);

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
    'From b24a8c17d3dbf878a134254c29505681dab4630b Mon Sep 17 00:00:00 2001
From: neustawebdeploy <mlist-web-deploy@neusta.de>
Date: Wed, 6 Jul 2016 16:55:18 +0200
Subject: [PATCH] Create TestBlubbub.md

---
 TestBlubbub.md | 4 ++++
 1 file changed, 4 insertions(+)
 create mode 100644 TestBlubbub.md

diff --git a/TestBlubbub.md b/TestBlubbub.md
new file mode 100644
index 0000000..d5e61b4
--- /dev/null
+++ b/TestBlubbub.md
@@ -0,0 +1,4 @@
+foo bar baz
+============
+
+hmm hmm.
'
);
