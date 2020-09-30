set -e
set -x

echo "INFO Backup known_hosts file"
cp ~/.ssh/known_hosts ~/.ssh/known_hosts.bak
ssh-keyscan -t rsa prod.api.docs.typo3.com >> ~/.ssh/known_hosts

ssh prod.api.docs.typo3.com@prod.api.docs.typo3.com << EOF
    mkdir -p /srv/vhosts/prod.api.docs.typo3.com/deployment/${bamboo.buildResultKey}
EOF

scp output output10 output9 prod.api.docs.typo3.com@prod.api.docs.typo3.com:/srv/vhosts/prod.api.docs.typo3.com/deployment/${bamboo.buildResultKey}

# unpack and publish docs
ssh prod.api.docs.typo3.com@prod.api.docs.typo3.com << EOF
source_dir="/srv/vhosts/prod.api.docs.typo3.com/deployment/${bamboo.buildResultKey}/"
cd ${source_dir} || exit 1

# master
mkdir output
tar xf output.tgz
cd output/html/
target_dir="/srv/vhosts/prod.api.docs.typo3.com/site/Web/master"
rm -Rf ${target_dir}
mkdir ${target_dir}
find . -maxdepth 1 ! -path . -exec mv -t ../../../../site/Web/master/ {} +

#10.4
cd ${source_dir}
mkdir output10
tar xf output10.tgz
cd output10/html/
target_dir="/srv/vhosts/prod.api.docs.typo3.com/site/Web/10.4"
rm -Rf ${target_dir}
mkdir ${target_dir}
find . -maxdepth 1 ! -path . -exec mv -t ../../../../site/Web/10.4/ {} +

#9.5
cd ${source_dir}
mkdir output9
tar xf output9.tgz
cd output9/html/
target_dir="/srv/vhosts/prod.api.docs.typo3.com/site/Web/9.5"
rm -Rf ${target_dir}
mkdir ${target_dir}
find . -maxdepth 1 ! -path . -exec mv -t ../../../../site/Web/9.5/ {} +

# And clean the temp deployment dir afterwards
#rm -rf ${source_dir}
EOF

echo "INFO Restore old known_hosts file"
cp ~/.ssh/known_hosts.bak ~/.ssh/known_hosts
