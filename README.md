# TYPO3 Intercept

Intercept is a small middleware that communicates between various services
used in TYPO3 core or core-near world.

Some processes and stats can be found in at
[TYPO3s Wiki](https://confluence.typo3.com/display/TC/Process+Flow+pre-merge+Tests).


## Gerrit to bamboo

A hook in gerrit calls intercept for each (core) push, intercept then
triggers the according bamboo core pre-merge test plan, depending on
given core branch.

