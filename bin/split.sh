#!/bin/bash

#
# Main split and push function
#
function splitForBranch {
    local LOCALBRANCH="$1"
    local REMOTEBRANCH="$2"
    local SPLITTER="$3"
    local EXTENSIONS="$4"

    echo "Handling core branch ${LOCALBRANCH} splitting to target branch ${REMOTEBRANCH}"

    for EXTENSION in ${EXTENSIONS}; do
        echo "Splitting extension ${EXTENSION}"

        # Split operation creating commit objects and giving last SHA1 commit hash
        SHA1=`./../intercept/bin/${SPLITTER} --prefix=typo3/sysext/${EXTENSION} --origin=origin/${LOCALBRANCH}`

        # Add target extension remote if needed
        if [[ $(git remote | grep "^${EXTENSION}\$" | wc -l) -eq 0 ]]; then
            echo "Adding remote"
            git remote add ${EXTENSION} git@github.com:TYPO3-CMS/${EXTENSION}.git
        fi
        # Update remote
        git fetch ${EXTENSION}

        # Push to remote
        git push ${EXTENSION} ${SHA1}:refs/heads/${REMOTEBRANCH}
    done
}


# Exit if not two argument are given
if [[ $# -ne 2 ]]; then
    echo "usage: $0 <sourceBranchName> <targetBranchName>"
    exit 1
fi

# Go to current dir this script is in
cd "$(dirname "$0")"

BASEREMOTE=git://git.typo3.org/Packages/TYPO3.CMS.git
REPODIR="../../TYPO3.CMS-split"

# Initial clone or update pull
if [[ -d ${REPODIR} ]]; then
    git -C ${REPODIR} pull
else
    git clone $BASEREMOTE ${REPODIR}
fi

# Go to repo checkout
cd ${REPODIR} || exit 1

# Find out which split binary to use
case "$(uname)" in
    Darwin)
        SPLITTER="splitsh-lite-darwin"
        ;;
    Linux)
        SPLITTER="splitsh-lite-linux"
        ;;
    *)
        echo 'Unknown OS'
        exit 1
        ;;
esac

EXTENSIONS=`ls typo3/sysext`

# Handle master branch
splitForBranch $1 $2 "${SPLITTER}" "${EXTENSIONS}"
