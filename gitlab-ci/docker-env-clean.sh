#!/bin/bash

set -xe

if [[ -z $DOCKER_IMAGE ]]; then
    error "Must be specific docker iamge for clean"
    exit 1
fi

DOCKER_HISTORY_LIMIT=${DOCKER_HISTORY_LIMIT:-2}


source $(dirname $0)/functions

docker_env_clean() {
    docker_history_list=`docker images | grep -i "${DOCKER_IMAGE} "`
    docker_history_num=`echo "${docker_history_list}" | wc -l`
    echo "${docker_history_list}"
    echo ${docker_history_num}
    echo ${DOCKER_HISTORY_LIMIT}

    if [ ${docker_history_num} -gt ${DOCKER_HISTORY_LIMIT} ]; then
        delete_num=$(( ${docker_history_num} - ${DOCKER_HISTORY_LIMIT} ))
        echo ${delete_num}
        echo "${docker_history_list}" | tail -n ${delete_num} | awk 'NR>1' | awk '{print $3 " deleting"}'
        echo "${docker_history_list}" | tail -n ${delete_num} | awk 'NR>1' | awk '{print $3}'  | xargs -I % docker rmi -f %
    fi
}


# 清理docker image
docker_env_clean

# 搞定权限
sudo chown  -R gitlab-runner:gitlab-runner vendor
