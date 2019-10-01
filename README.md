# TYPO3 Intercept

Intercept is a small middleware that communicates between various services
used in TYPO3 core or core-near world. Intercept can be found online at
https://intercept.typo3.com.

Processes, setup, architecture and so on are found in this README. Minor
additional information can be found (for TYPO3 GmbH users) at the
[TYPO3s Wiki](https://confluence.typo3.com/display/TC/Process+Flow+pre-merge+Tests).


## Services

#### Bamboo post build
End point "/bamboo" - Called by bamboo when a core build finished. Triggers votes
on gerrit for pending (pre-merge) patches, retriggers a failed nightly build once,
escalates a finally failed nightly core build to slack.

#### Docs to bamboo
Hook end point for domain "docs-hook.typo3.com" (primary) or "intercept.typo3.com/docs"
(test use only!). Triggers rendering and deployment of a documentation to new docs server.

##### Legacy Documentation Rendering Hook
Please check the information in `legacy_hook/` folder

#### Gerrit to bamboo
End point "/gerrit". A hook fired by gerrit for core patch push events to trigger
a bamboo pre-merge build.

#### Github pull request
End point "/githubpr" - Hook fired by github TYPO3 core main mirror if a pull request
has been pushed to github to transfer that PR to a forge issue and a gerrit review.

#### Git subtree split
End point "/split" - Hook fired by github core main mirror https://github.com/typo3/typo3.cms/ for new
pushes (merged patch / new tag), used to update the git split packages at https://github.com/typo3-cms/.
Sub tree splitting and tagging takes a while, jobs are queued with a rabbitmq and a single
symfony command cli worker does the main job.

#### Bitbucket to packagist hook
Sends bitbucket webhook push events to packagist to update according packages as packagist currently cannot
use the bitbucket *server* payloads directly. Requests to the hook have to be sent via https://intercept.typo3.com/bitbucketToPackagist?apiToken=token&username=user

## Web interface

#### Bamboo control
Trigger single bamboo builds manually for core patches.

#### Git subtree split control
Trigger subtree splitting and tagging manually.

#### Docs control
Interface to deal with documentation rendering and management.

#### Discord Webhooks
Interface to link other services to Discord webhooks.


## List of services interacted with

* forge.typo3.com - Create an issue on forge if a github core pull request is transformed
  to a gerrit core patch and forge issue.
* review.typo3.com (gerrit) - Gerrit calls /gerrit if new core patches are pushed, intercept
  votes on gerrit for completed bamboo builds, intercept pushes patches to gerrit if a
  github pull request is transformed to a gerrit core patch and forge issue.
* bamboo.typo3.com - Trigger test builds for core patches, trigger documentation rendering builds
* github.com - repository hooks trigger: git subtree split, git subtree tagging, pull request
  handling, documentation rendering. intercept pushes patches and tags to core subtree split
  repositories.
* rabbitmq.typo3.com - intercept web controls push new subtree split & tag jobs to a rabbitmq queue,
  a intercept cli job connects to rabbit to handle these jobs.
* elk.typo3.com (graylog) - intercept logs details to graylog, the web intercept interface reads various
  log entries and renders them.
* typo3.slack.com - intercept pushs messages to slack for failed nightly builds
* sqlite - a local sqlite, stores users, documentation details, information if a single core
  nightly build has been rebuild already
* Discord -  Creating and sending webhooks in Discord


## Architecture

intercept is sort of a spider that hangs in between different services to communicate
and translate between them. On testing side, only the simple data munging parts are
unit tested, the main testing logic lies in the functional tests. The coverage is very
hight to specify in detail what intercept does, and which data is expected from a given
service.


#### Class folders
* Bundle/ contains a helper class for functional testing
* Client/ contains HTTP clients injected into Services to execute remote calls
* Command/ contains a symfony console cli worker that connects to a rabbitmq and
  does the core subtree splitting and tagging
* Controller/ contains main controller classes for web and api endpoints
* Creator/ contains value objects created by controllers, eg. a specific gerrit
  review message to be posted
* Entity/ contains doctrine database entity objects
* Exception/ contains custom exceptions
* Extractor/ contains value objects usually created by services, eg. a class representing
  a github pull request
* Form/ contains classes representing the various web forms
* GitWrapper/ contains helper classes for details with managing git repositories via PHP.
  This is used for the github pull request to gerrit service, and by the sub tree split worker.
* Migrations/ contains doctrine database migration classes.
* Monolog/ contains helper classes for logging data to graylog
* Repository/ contains doctrine orm repositories
* Service/ contains classes doing the heavy lifting, usually calling Client/ objects and
  returning Extractor/ objects
* Utility/ contains static helper stuff like date munging or a semver helper


## Git branches

Changes to intercept should go to the develop branch. This branch is deployed to life
https://intercept.typo3.com/ immediately by bamboo deployment if the test build goes green.
The master branch is currently not maintained.

Test coverage can be found (for TYPO3 GmbH members) at https://bamboo.typo3.com/browse/T3G-IN
-> a single run -> the test job -> tab 'Artifacts' for the raw html output by phpunit and
tab 'Clover' for a summary graph.


## Installation & upgrading

Notes: the ddev based setup does currently NOT start the rabbitmq server and the core
split / tag worker. Some further setup is not fully finished, like valid credentials
for third party services. The documented setup has been created to allow easy
development of the documentation part - if more is needed, have a look at the .env
file and write proper values to a .env.local file!

### First install ddev based

On linux, the elasticsearch container for graylog needs an kernel argument: On the host,
edit file /etc/sysctl.conf and add:

```
$ sudo vi /etc/sysctl.conf

# elasticsearch processes / containers
vm.max_map_count = 262144
```

Then, either reboot, or issue command `sudo sysctl -w vm.max_map_count=262144` once.

* Clone repo
* $ ddev start
* $ ddev composer install
* $ ddev exec bin/console doctrine:migrations:migrate -n
* $ ddev exec yarn install
* $ ddev exec yarn encore dev
* $ docker cp .ddev/graylogmongo/dump/ ddev-intercept-graylogmongo:/dump
* $ ddev exec -s graylogmongo mongorestore -d graylog /dump/graylog
* $ ddev exec -s graylogmongo rm -rf /dump

### URL's

* http://intercept.ddev.local/admin/ - intercept web interface
* http://intercept.ddev.local:9101/ - graylog interface, user: admin, password: foo
* http://intercept.ddev.local:15672/ - rabbitmq interface, user: admin, password: foo

### Upgrading ddev based

* $ git pull
* $ ddev start
* $ ddev composer install
* $ ddev exec bin/console cache:clear
* $ ddev exec bin/console doctrine:migrations:migrate -n
* $ ddev exec yarn install
* $ ddev exec yarn encore dev
* $ docker cp .ddev/graylogmongo/dump/ ddev-intercept-graylogmongo:/dump
* $ ddev exec -s graylogmongo mongorestore -d graylog /dump/graylog
* $ ddev exec -s graylogmongo rm -rf /dump
* $ ddev exec -s rabbitmq rabbitmqadmin -u admin -p foo declare queue name=intercept-core-split-testing

### Development

If changing js / css / web images, files to public dirs need to be recompiled and published:

* $ ddev exec yarn encore dev

An alternative is to start an explicit watcher process to recompile if css files change:

* $ ddev exec yarn encore dev --watch

### Discord

To use the Discord part of Intercept you need two variables in your `.env.local` file
* DISCORD_SERVER_ID
* DISCORD_BOT_TOKEN

The server ID is the ID of the Discord server you wish to interact with. You can find this out by turning on developer 
mode in Discord, right clicking the server, and then clicking `copy id`.

The Bot is a token needed to interact with the Discord API. You can read more about these [here](https://discordapp.com/developers/docs/topics/oauth2#bots).
You also need to make sure the bot you are using is a member of the server you are using!

Once set up you must run the command `bin/console app:discord-sync`. This commend will fetch a list of Discord channels to your Intercept installation.
In a production environment, this command should be set as a cronjob for roughly every 10 minutes.

### Test execution

#### Unit tests

* $ ddev composer t3g:test:php:unit
* OR $ ddev exec bin/phpunit -c build/phpunit.xml --testsuite "Unit Test Suite"

#### Functional tests

* $ ddev composer t3g:test:php:functional
* OR $ ddev exec bin/phpunit -c build/phpunit.xml --testsuite "Functional Test Suite"

#### Unit and functional tests with coverage

Find rendered coverage data at var/phpunit/coverage/index.html

* $ ddev composer t3g:test:php:cover
* OR $ ddev exec bin/phpunit -c build/phpunit.xml --log-junit var/phpunit/phpunit.xml  --coverage-clover var/phpunit/coverage.xml --coverage-html var/phpunit/coverage/

### Fix CGL

* $ ddev composer t3g:cgl
* OR $ ddev exec php-cs-fixer fix --config build/.php_cs.dist --format=junit > var/php-cs-fixer/php-cs-fixer.xml

### Creating a new mongo dump

The graylog configuration is stored in mongodb. If fiddling with the interface and
adding stuff, the mongodb should be dumped afterwards and committed for other ddev
users to fetch this new config. Note: Do not use important passwords on the ddev
based graylog instance, those would be within that dump!

* $ ddev exec -s graylogmongo rm -rf /dump
* $ ddev exec -s graylogmongo mongodump --out /dump
* $ cd .ddev/graylogmongo
* $ rm -rf dump
* $ docker cp ddev-intercept-graylogmongo:/dump . 
* $ ddev exec -s graylogmongo rm -rf /dump
* commit stuff to git

### BlackfireIo

If the instance runs with ddev, a .env.local file inside `.ddev` is mandatory. It can be copied from the existing .env.example
file.
For no performance profiling tasks, the values may be empty. Else, put your account data here. If in doubt, reach out to Susi.
