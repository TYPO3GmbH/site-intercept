#!/bin/bash

rootdir=$(pwd)
clone_dir=../var/docs-build/
args=("$@")
buildinfo=${args[0]}

mkdir -p $clone_dir

cd $rootdir || exit
source $buildinfo
mkdir -p $clone_dir$vendor
cd $clone_dir$vendor
echo $vendor/$name
git clone "$repository_url" "$name" 2> /dev/null || (cd "$name" ; git fetch --all 2> /dev/null)
rm -Rf $name/RenderedDocumentation
rm -Rf $name/FinalDocumentation

