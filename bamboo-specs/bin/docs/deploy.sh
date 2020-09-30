set -e
set -x

echo "INFO Backup known_hosts file"
cp ~/.ssh/known_hosts ~/.ssh/known_hosts.bak
ssh-keyscan -t rsa prod.docs.typo3.com >> ~/.ssh/known_hosts

ssh prod.docs.typo3.com@prod.docs.typo3.com << EOF
    mkdir -p /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}
EOF

scp docs.tgz prod.docs.typo3.com@prod.docs.typo3.com:/srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}

# unpack and publish docs
ssh prod.docs.typo3.com@prod.docs.typo3.com << EOF
cd /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}

mkdir documentation_result
tar xf docs.tgz -C documentation_result

source "documentation_result/deployment_infos.sh"

web_dir="/srv/vhosts/prod.docs.typo3.com/site/Web"
target_dir="${web_dir}/${type_short:?type_short must be set}/${vendor:?vendor must be set}/${name:?name must be set}/${target_branch_directory:?target_branch_directory must be set}"

echo "Deploying to $target_dir"

mkdir -p $target_dir
rm -rf $target_dir/*

mv documentation_result/FinalDocumentation/* $target_dir

# Re-New symlinks in document root if homepage repo is deployed
# And some other homepage specific tasks
if [ "${type_short}" == "h" ] && [ "${target_branch_directory}" == "master" ]; then
    cd $web_dir
    # Remove existing links (on first level only!)
    find . -maxdepth 1 -type l | while read line; do
        	    rm -v "$line"
    done
    # link all files in deployed homepage repo to doc root
    ls h/typo3/docs-homepage/master/en-us/ | while read file; do
        	    ln -s "h/typo3/docs-homepage/master/en-us/$file"
    done
    # Copy js/extensions-search.js to Home/extensions-search.js to
    # have this file parallel to Home/Extensions.html
    cp js/extensions-search.js Home/extensions-search.js
    # Touch the empty and unused system-exensions.js referenced by the extension search
    touch Home/systemextensions.js
fi

# Fetch latest "static" extension list from intercept (this is a php route!)
# and put it as Home/extensions.js to be used by Home/Extensions.html
curl https://intercept.typo3.com/assets/docs/extensions.js --output ${web_dir}/Home/extensions.js

rm -rf /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}
EOF

echo "INFO Restore old known_hosts file"
cp ~/.ssh/known_hosts.bak ~/.ssh/known_hosts
