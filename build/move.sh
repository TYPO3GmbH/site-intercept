#!/bin/bash

rootdir=$(pwd)
args=("$@")
buildinfo=${args[0]}
render_dir=../var/docs-render
clone_dir=../var/docs-build/

mkdir -p $render_dir

cd $rootdir || exit
source $buildinfo


mkdir -p $render_dir/$type_short/$vendor/$name
echo $render_dir/$type_short/$vendor/$name
rsync -ahWz --no-compress $clone_dir$vendor/$name/FinalDocumentation/ $render_dir/$type_short/$vendor/$name/
