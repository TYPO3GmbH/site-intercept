#!/bin/bash
### CORE MANUALS /c/
wget -x --cut-dirs=0 -nH https://docs.typo3.org/c/typo3/cms-seo/12.4/en-us/objects.inv.json
wget -x --cut-dirs=0 -nH https://docs.typo3.org/c/typo3/cms-seo/13.4/en-us/objects.inv.json
wget -x --cut-dirs=0 -nH https://docs.typo3.org/c/typo3/cms-seo/main/en-us/objects.inv.json

wget -x --cut-dirs=0 -nH https://docs.typo3.org/c/typo3/cms-core/main/en-us/objects.inv.json

### OFFICIAL DOCS with Inventory /m/
wget -x --cut-dirs=0 -nH https://docs.typo3.org/m/typo3/guide-example-extension-manual/main/en-us/objects.inv.json
wget -x --cut-dirs=0 -nH https://docs.typo3.org/m/typo3/guide-example-extension-manual/draft/en-us/objects.inv.json

wget -x --cut-dirs=0 -nH https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/objects.inv.json
wget -x --cut-dirs=0 -nH https://docs.typo3.org/m/typo3/reference-coreapi/13.4/en-us/objects.inv.json
wget -x --cut-dirs=0 -nH https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/objects.inv.json

### OTHER TYPO3-docs without inventory
wget -x --cut-dirs=0 -nH https://docs.typo3.org/other/typo3/view-helper-reference/13.4/en-us/objects.inv.json
wget -x --cut-dirs=0 -nH https://docs.typo3.org/other/typo3/view-helper-reference/main/en-us/objects.inv.json

wget -x --cut-dirs=0 -nH https://docs.typo3.org/other/t3docs/render-guides/main/en-us/objects.inv.json

### THIRD PARTY DOCS
wget -x --cut-dirs=0 -nH https://docs.typo3.org/p/georgringer/news/main/en-us/objects.inv.json
wget -x --cut-dirs=0 -nH https://docs.typo3.org/p/georgringer/news/12.1/en-us/objects.inv.json
wget -x --cut-dirs=0 -nH https://docs.typo3.org/p/georgringer/news/10.0/en-us/objects.inv.json
