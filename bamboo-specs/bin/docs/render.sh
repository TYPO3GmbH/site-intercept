if [ "$(ps -p "$$" -o comm=)" != "bash" ]; then
bash "$0" "$@"
exit "$?"
fi

set -e
set -x

# fetch build information file and source it
curl https://intercept.typo3.com/${bamboo_BUILD_INFORMATION_FILE} --output deployment_infos.sh
source deployment_infos.sh || (echo "No valid deployment_infos.sh file found"; exit 1)

# clone repo to project/ and checkout requested branch / tag
mkdir project
git clone ${repository_url} project
cd project && git checkout ${source_branch}
cd ..

touch project/jobfile.json
cat << EOF > project/jobfile.json
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

cd project
curl -o git-restore-mtime-modified.py https://raw.githubusercontent.com/marble/Toolchain_RenderDocumentation/master/16-Convert-and-fix-and-check/git-restore-mtime/git-restore-mtime-modified.py
python git-restore-mtime-modified.py --destfile-gitloginfo=.gitloginfo-GENERATED.json
cd ..

function renderDocs() {
    docker run \
        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/project:/PROJECT \
        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/RenderedDocumentation/:/RESULT \
        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \
        --rm \
        --entrypoint bash \
        t3docs/render-documentation:v2.6.1 \
        -c "/ALL/Menu/mainmenu.sh makehtml -c replace_static_in_html 1 -c make_singlehtml 1 -c jobfile /PROJECT/jobfile.json; chown ${HOST_UID} -R /PROJECT /RESULT"
}
mkdir -p RenderedDocumentation
mkdir -p FinalDocumentation

# main render call - will render main documentation and localizations
renderDocs

# test if rendering failed for whatever reason
ls RenderedDocumentation/Result || exit 1

# if a result has been rendered for the main directory, we treat that as the 'en_us' version
if [ -d RenderedDocumentation/Result/project/0.0.0/ ]; then
        echo "Handling main doc result as en-us version"
        mkdir FinalDocumentation/en-us
        # Move en-us files to target dir, including dot files
        (shopt -s dotglob; mv RenderedDocumentation/Result/project/0.0.0/* FinalDocumentation/en-us)
        # evil hack to get rid of hardcoded docs.typo3.org domain name in version selector js side
        # not needed with replace_static_in_html at the moment
        # sed -i 's%https://docs.typo3.org%%' FinalDocumentation/en-us/_static/js/theme.js
        # Remove the directory, all content has been moved
        rmdir RenderedDocumentation/Result/project/0.0.0/
        # Remove a possibly existing Localization.en_us directory, if it exists
        rm -rf RenderedDocumentation/Result/project/en-us/
fi

# now see if other localization versions have been rendered. if so, move them to FinalDocumentation/, too
if [ "$(ls -A RenderedDocumentation/Result/project/)" ]; then
    for LOCALIZATIONDIR in RenderedDocumentation/Result/project/*; do
            LOCALIZATION=`basename $LOCALIZATIONDIR`
            echo "Handling localized documentation version ${LOCALIZATION:?Localization could not be determined}"
            mkdir FinalDocumentation/${LOCALIZATION}
            (shopt -s dotglob; mv ${LOCALIZATIONDIR}/0.0.0/* FinalDocumentation/${LOCALIZATION})
            # Remove the localization dir, it should be empty now
            rmdir ${LOCALIZATIONDIR}/0.0.0/
            rmdir ${LOCALIZATIONDIR}
            # evil hack to get rid of hardcoded docs.typo3.org domain name in version selector js side
            # not needed with replace_static_in_html at the moment
            # sed -i 's%https://docs.typo3.org%%' FinalDocumentation/${LOCALIZATION}/_static/js/theme.js
    done
fi

rm -rf RenderedDocumentation

# archive rendered docs
tar -cfz docs.tgz FinalDocumentation deployment_infos.sh
