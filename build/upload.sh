#!/bin/bash

rootdir=$(pwd)
render_dir=../var/docs-render
mkdir -p $render_dir
source ../.env
source ../.env.local

cd $rootdir || exit

rsync -azc --stats $render_dir/p/ $SSH_LIVE_USER@docs.typo3.org:"$SSH_LIVE_TARGET_DIR/p"
rsync -azc --stats $render_dir/other/ $SSH_LIVE_USER@docs.typo3.org:"$SSH_LIVE_TARGET_DIR/other"
rsync -azc --stats $render_dir/m/ $SSH_LIVE_USER@docs.typo3.org:"$SSH_LIVE_TARGET_DIR/m"
