# Legacy documentation magic

This is currently deployed to the docs server web document root directory services/.
It contains some meta scripts to keep compatibility with the old documentation server

## ajaxversions.php

This script is called when clicking the 'Versions' selector in the lower
left of a rendered documentation. It returns a list of existing "other versions"
of that documentation. 

## ajaxdownloads.php

Similar to ajaxversions.php. This script outputs links to _buildinfo directory
of the given rendered repository.

## 404.php

This php file is registered as nginx services/404.php 404 error handler. It traverses
the document root path up from the orgininally requested file until it find the first
404.html. Documentation repositories can have own 404.rst files this way. 


## Hook for documentation rendering

The old documentation rendering hook is available under https://docs.typo3.org/services/handle_github_post_receive_hook.php

This old hook is used as GitHub webhook and exists in a lot of repositories. To migrate the old hook, the requests to this hook are forwarded to the new hook system.

This folder can be removed after e.g. one year.

bamboo will deploy this folder on the new docs server and make the old hook URL available again.

For the reasons above, this folder has its own README file and an own composer.json file.

## Installation

    # checkout this folder and....
    composer install