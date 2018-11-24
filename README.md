# TYPO3 Intercept

Intercept is a small middleware that communicates between various services
used in TYPO3 core or core-near world.

Some processes and stats can be found in at
[TYPO3s Wiki](https://confluence.typo3.com/display/TC/Process+Flow+pre-merge+Tests).


## Architecture

* Client/ contain HTTP clients injected into Services to execute remote calls
* Creator/ contain value objects created by controllers, eg. a specific gerrit review message to be posted
* Extractor/ contain value objects usually created by services, eg. a class representing a github pull request
* Service/ contain classes doing the heavy lifting, usually calling Client/ objects and returning Extractor/ objects


## Bamboo post build

End point "/bamboo" - Called by bamboo when it finished a core pre-merge test. May later be used
to be called if a nightly build finished, too.


## Docs to bamboo
End point for "docs-hook.typo3.com" and "/docs" for all domains - Used by github push (currently)
to trigger rendering of a repository that contains documentation.


## Gerrit to bamboo

End point "/gerrit" - A hook fired by gerrit for core patch push events to trigger a bamboo pre-merge build.


## Github pull request

End point "/githubpr" - Hook fired by github core main mirror if a pull request has been pushed to
github to transfer that PR to a forge issue and gerrit review.

## Git subtree split

End point "/split" - Hook fired by github core main mirror https://github.com/typo3/typo3.coms/ for new
pushes (merged patch / new tag), used to update the git split packages at https://github.com/typo3-cms/. 