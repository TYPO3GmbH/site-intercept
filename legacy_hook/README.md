# Legacy Hook for documentation rendering

The old documentation rendering hook is available under https://docs.typo3.org/services/handle_github_post_receive_hook.php

This old hook is used as GitHub webhook and exists in a lot of repositories. To migrate the old hook, the requests to this hook are forwarded to the new hook system.

This folder can be removed after e.g. one year.

bamboo will deploy this folder on the new docs server and make the old hook URL available again.

For the reasons above, this folder has its own README file and an own composer.json file.

## Installation

    # checkout this folder and....
    composer install