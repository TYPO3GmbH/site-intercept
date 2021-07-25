#!/bin/bash

rootdir=$(pwd)
args=("$@")
buildinfo=${args[0]}
clone_dir=../var/docs-build/

cd $rootdir || exit
source $buildinfo
cd $clone_dir$vendor/$name || exit
echo "---------------------------------" $vendor/$name/$target_branch_directory "-------------------------------"
git clean -f > /dev/null 2>&1
git checkout $source_branch > /dev/null 2>&1

touch jobfile.json
cat << EOF > jobfile.json
{
  "Overrides_cfg": {
      "general": {
          "release": "$target_branch_directory"
      },
      "html_theme_options": {
          "docstypo3org": "yes"
      }
  }
}
EOF
  rm -Rf FinalDocumentation/$target_branch_directory
  rm -Rf RenderedDocumentation/$target_branch_directory
  mkdir -p RenderedDocumentation/$target_branch_directory
  mkdir -p FinalDocumentation/$target_branch_directory
  docker run --rm --user=$(id -u):$(id -g) \
    -v $(pwd):/PROJECT:ro \
    -v $(pwd)/RenderedDocumentation/$target_branch_directory:/RESULT \
    t3docs/render-documentation makehtml \
      -c make_singlehtml 1 \
      -c "/ALL/Menu/mainmenu.sh makehtml -c replace_static_in_html 1 -c make_singlehtml 1 -c jobfile /PROJECT/jobfile.json" > /dev/null 2>&1

  # if a result has been rendered for the main directory, we treat that as the 'en_us' version
  if [ -d RenderedDocumentation/$target_branch_directory/Result/project/0.0.0/ ]; then
          echo "Handling main doc result as en-us version"
          mkdir -p FinalDocumentation/$target_branch_directory/en-us
          # Move en-us files to target dir, including dot files
          (shopt -s dotglob; mv RenderedDocumentation/$target_branch_directory/Result/project/0.0.0/* FinalDocumentation/$target_branch_directory/en-us)
          # Remove the directory, all content has been moved
          rmdir RenderedDocumentation/$target_branch_directory/Result/project/0.0.0/
          # Remove a possibly existing Localization.en_us directory, if it exists
          rm -rf RenderedDocumentation/$target_branch_directory/Result/project/en-us/
  fi

  # now see if other localization versions have been rendered. if so, move them to FinalDocumentation/, too
  if [ -d RenderedDocumentation/$target_branch_directory/Result/project/ ]; then
  if [ "$(ls -A RenderedDocumentation/$target_branch_directory/Result/project/)" ]; then
      for LOCALIZATIONDIR in RenderedDocumentation/$target_branch_directory/Result/project/*; do
              LOCALIZATION=`basename $LOCALIZATIONDIR`
              echo "Handling localized documentation version ${LOCALIZATION:?Localization could not be determined}"
              mkdir FinalDocumentation/$target_branch_directory/${LOCALIZATION}
              (shopt -s dotglob; mv ${LOCALIZATIONDIR}/0.0.0/* FinalDocumentation/$target_branch_directory/${LOCALIZATION})
              # Remove the localization dir, it should be empty now
              rmdir ${LOCALIZATIONDIR}/0.0.0/
              rmdir ${LOCALIZATIONDIR}
      done
  fi
  fi


echo "This script took $SECONDS seconds to execute"
