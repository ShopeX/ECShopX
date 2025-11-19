#!/bin/bash

set -xe

if [[ -z $DOCKER_IMAGE || -z $DOCKER_USER || -z $DOCKER_PASS ]]; then
    error "DOCKER not configured. Aborting..."
    exit 1
fi

if [[ -z $SWOOLE_COMPILER_KEY || -z $SWOOLE_COMPILER_KEY_NAME || -z $SWOOLE_COMPILER_HOST || -z $SWOOLE_COMPILER_HOST_NAME ]]; then
    error "Swoole compiler  not configured. Aborting..."
    exit 1
fi


CACHE_DIR=${CACHE_DIR:-/var/cache}
DOCKE_DIR=${DOCKERFILE_DIR:-docker}
COMPOSER_CACHE_DIR=${CACHE_DIR}/composer
DOCKER_CACHE_DIR=${CACHE_DIR}/docker
DOCKER_REGISTRY=${DOCKER_IMAGE%%/*}

DOCKER_SHOPEX_REGISTRY=${DOCKER_SHOPEX_IMAGE%%/*}
#PHP_COMPOSER_IMAGE=hub.ishopex.cn/espier-docker-library/php:7.2-composer-alpine3.7
PHP_COMPOSER_IMAGE=registry.cn-hangzhou.aliyuncs.com/espier/php:7.2-composer-alpine3.12

SWOOLE_COMPILER_ECSHOPX_PACKAGE_PATH=/ShopexEncrypt/SwooleManage/compiler-swoole/storage/data/package/fla/ecshopx
SWOOLE_COMPILER_ECSHOPX_UNENCRYPTED_CODE_PATH=${SWOOLE_COMPILER_ECSHOPX_PACKAGE_PATH}/src/CompanysBundle/Ego
SWOOLE_COMPILER_ECSHOPX_ENCRYPTED_CODE_PATH=/ShopexEncrypt/SwooleManage/compiler-swoole/storage/data/package/egos/ecshopx
SWOOLE_COMPILER_ECSHOPX_LICENSE_PATH=/ShopexEncrypt/SwooleManage/compiler-swoole/storage/data/license/ecshopx


source $(dirname $0)/functions

ssh_configure() {
    local SSH_CONFIG_DIR=~/.ssh

    mkdir -p $SSH_CONFIG_DIR

    if [ ! -f $SSH_CONFIG_DIR/config ]; then
        touch $SSH_CONFIG_DIR/config
    fi

    if ! cat $SSH_CONFIG_DIR/config | grep -qi ${SWOOLE_COMPILER_HOST_NAME}; then

	echo $SWOOLE_COMPILER_KEY | base64 -d > $SSH_CONFIG_DIR/${SWOOLE_COMPILER_KEY_NAME}.key
	{ \
	  echo "HOST    ${SWOOLE_COMPILER_HOST}"; \
	  echo "    User root"; \
	  echo "    Hostname ${SWOOLE_COMPILER_HOST_NAME}"; \
	  echo "    RSAAuthentication yes"; \
	  echo "    IdentityFile $SSH_CONFIG_DIR/${SWOOLE_COMPILER_KEY_NAME}.key";
	} > $SSH_CONFIG_DIR/config
    fi

    chmod 644 $SSH_CONFIG_DIR/config
    chmod 600 $SSH_CONFIG_DIR/${SWOOLE_COMPILER_KEY_NAME}.key
}

docker_pull_parent_image () {
    local docker_file=$1
    local parent_images=`sed -n  's|^FROM[[:blank:]]*\([[:graph:]]*\).*|\1|p' ${docker_file} | uniq`
    for parent_image in ${parent_images[@]};do
        info "docker pull ${parent_image}"
        docker pull $parent_image
    done
}

build_composer() {
    local CMD=$@
    docker pull ${PHP_COMPOSER_IMAGE}
    docker run -e COMPOSER_CACHE_DIR="${COMPOSER_CACHE_DIR}" \
           -v ${COMPOSER_CACHE_DIR}:${COMPOSER_CACHE_DIR} \
           -v ${CI_PROJECT_DIR}:/app ${PHP_COMPOSER_IMAGE}  $CMD
}

build_vendor() {
    info "Building vendor... "
    #build_composer "composer install --prefer-dist --no-autoloader --no-dev"
    build_composer "php -d memory_limit=-1 /usr/bin/composer install --prefer-dist --no-autoloader --no-dev"
}

build_dump_autoload() {
    info "Building dump autoload"
    build_composer "composer dump-autoload --optimize"
}

prepare_code() {
    local APP_VERSION=$1
    local package_name=ecshopx-${APP_VERSION}.tar

    ssh_configure

    ## 处理 swoole-compiler.config

    sed -i "s|^php_files_path=[[:graph:]]*|php_files_path=${SWOOLE_COMPILER_ECSHOPX_UNENCRYPTED_CODE_PATH}|g" ego/swoole-compiler.config
    sed -i "s|^compiled_archived_path=[[:graph:]]*|compiled_archived_path=${SWOOLE_COMPILER_ECSHOPX_ENCRYPTED_CODE_PATH}/${package_name}|g" ego/swoole-compiler.config

    cat ego/swoole-compiler.config
    ## swoole-compiler服务处理
    ssh ${SWOOLE_COMPILER_HOST} "cd ${SWOOLE_COMPILER_ECSHOPX_PACKAGE_PATH};git stash;git pull;git checkout ${CI_COMMIT_SHA}"
    scp ego/swoole-compiler.config ${SWOOLE_COMPILER_HOST}:${SWOOLE_COMPILER_ECSHOPX_PACKAGE_PATH}/ego/swoole-compiler.config
    ssh ${SWOOLE_COMPILER_HOST} "/bin/swoole-compiler -c ${SWOOLE_COMPILER_ECSHOPX_PACKAGE_PATH}/ego/swoole-compiler.config"

    cd  ${CI_PROJECT_DIR}/src/CompanysBundle/Ego
    ssh ${SWOOLE_COMPILER_HOST} "cat ${SWOOLE_COMPILER_ECSHOPX_ENCRYPTED_CODE_PATH}/${package_name}" | tar -xz --file -
    cd  ${CI_PROJECT_DIR}

    # find ${CI_PROJECT_DIR}/src/CompanysBundle/Ego -type f | xargs -I % cat %
    # 生成license
    sed -i "s|^license_file=[[:graph:]]*|license_file=${SWOOLE_COMPILER_ECSHOPX_LICENSE_PATH}/license.zl|g" ego/license.config
    scp ego/license.config ${SWOOLE_COMPILER_HOST}:${SWOOLE_COMPILER_ECSHOPX_PACKAGE_PATH}/ego/license.config
    ssh ${SWOOLE_COMPILER_HOST} "/bin/swoole-compiler -t license -c ${SWOOLE_COMPILER_ECSHOPX_PACKAGE_PATH}/ego/license.config"

    scp ${SWOOLE_COMPILER_HOST}:${SWOOLE_COMPILER_ECSHOPX_LICENSE_PATH}/license.zl ${CI_PROJECT_DIR}/license.zl

    ls -lah license.zl
    # clean

    rm -rf .gitlab-ci.yml gitlab-ci/docker-development-image.sh ego
}

docker_login ${DOCKER_REGISTRY} ${DOCKER_USER} ${DOCKER_PASS}
docker_pull_parent_image ${DOCKERFILE_DIR}/Dockerfile


UPDATED_FILES=`git diff --name-only ${CI_COMMIT_SHA}~ ${CI_COMMIT_SHA}`

# 调试 star 临时注释
build_vendor
build_dump_autoload
sudo chown -R gitlab-runner:gitlab-runner vendor

sudo mkdir -p ${CACHE_DIR}




#APP_VERSION=`git describe`;APP_VERSION=${APP_VERSION%-*}
APP_VERSION=`git describe`
export APP_VERSION=${APP_VERSION}

echo ${APP_VERSION} > version.txt

# gitlab-ci runner for ssh executor
docker_pull_parent_image ${DOCKERFILE_DIR}/Dockerfile


# 准备star环境
if [[ ${IS_STAR} == "true" ]]; then
    #DOCKER_IMAGE=${DOCKER_IMAGE}-custom
    APP_VERSION=star-${APP_VERSION}
    find . -maxdepth 1 -type f | grep -i .env | grep -v  .env.star | xargs -I % rm %

    rm -rf ./src/CompanysBundle/Console/UpdateCompanyLisensCommand.php
    mv .env.star .env.production
fi

prepare_code $APP_VERSION

docker build . -f ${DOCKERFILE_DIR}/Dockerfile \
       -t $DOCKER_IMAGE:$APP_VERSION \
       --build-arg DOCKERFILE_DIR=${DOCKERFILE_DIR}
       #--target prod

if [[ ${IS_STAR} == "true" ]]; then
    # 归档
    docker run --entrypoint=tar $DOCKER_IMAGE:$APP_VERSION --create --file - --one-file-system --directory /usr/src/html . > ${APP_VERSION}.tar
    tar --delete ./vendor -vf ${APP_VERSION}.tar
fi

docker push $DOCKER_IMAGE:$APP_VERSION

docker_login ${DOCKER_SHOPEX_REGISTRY} ${DOCKER_SHOPEX_USER} ${DOCKER_SHOPEX_PASS}
docker tag $DOCKER_IMAGE:$APP_VERSION $DOCKER_SHOPEX_IMAGE:$APP_VERSION
docker push $DOCKER_SHOPEX_IMAGE:$APP_VERSION
