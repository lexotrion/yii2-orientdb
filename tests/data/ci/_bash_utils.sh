#!/bin/bash

build(){

    if command_exists "mvn" && [[ "$1" == *"-"* ]]; then
        echo "Build by clone/pull github develop branch"
        build_via_git $1 $2
    else
        echo "Build by download from github repository"
        get_compiled $1 $2
    fi

}

command_exists () {
  type "$1" >/dev/null 2>&1 ;
}

extract(){

    ODB_PACKAGE_PATH=$1
    filename=$(basename "${ODB_PACKAGE_PATH}")
    CI_DIR=$2

    echo "Extract archive: ${filename}"
    if [ ${filename#*tar.gz} ]; then
        # empty string found, means no tar archive extension found
        echo "unzip -q ${ODB_PACKAGE_PATH} -d ${CI_DIR}"
        unzip -q ${ODB_PACKAGE_PATH} -d ${CI_DIR}
    elif [ ${filename#*zip} ]; then
        # empty string found, means no zip archive extension found
        echo "tar xf ${ODB_PACKAGE_PATH} -C ${CI_DIR}"
        tar xf ${ODB_PACKAGE_PATH} -C ${CI_DIR}
    else
        echo "Unknown file type"
        exit 1
    fi;

    echo "rm -rf ${ODB_PACKAGE_PATH}"
    rm -rf ${ODB_PACKAGE_PATH}

}

download () {
    OUTPUT_DIR=$2
    PACKAGE_NAME=$3

    if [ ! -d "$OUTPUT_DIR" ]; then
        mkdir "$OUTPUT_DIR"
    fi

    if command_exists "wget" ; then
        echo "wget -q -O $OUTPUT_DIR/$PACKAGE_NAME $1"
        wget -q -O "$OUTPUT_DIR/$PACKAGE_NAME" "$1"
    elif command_exists "curl" ; then
        echo "cd ${OUTPUT_DIR}"
        cd ${OUTPUT_DIR}
        echo "curl --silent -L $1 \"$OUTPUT_DIR/$PACKAGE_NAME\""
        curl --silent -L $1 --output "$OUTPUT_DIR/$PACKAGE_NAME"
    else
        echo "Cannot download $1 [missing wget or curl]"
        exit 1
    fi
}

build_via_git (){

    ODB_VERSION=$1
    CI_DIR=$2

    cd ${CI_DIR}
    if [ ! -d "orientdb-develop" ]; then
        echo "No git clone found."
        echo "git clone https://github.com/orientechnologies/orientdb.git orientdb-develop"
        git clone https://github.com/orientechnologies/orientdb.git orientdb-develop
        cd orientdb-develop
        git checkout ${ODB_VERSION}
    else
        echo "Git clone found. Updating."
        cd orientdb-develop
        git checkout ${ODB_VERSION}
    fi

    git pull origin ${ODB_VERSION}
    mvn clean install

    echo "mv ${CI_DIR}/releases/* ${CI_DIR}"
    mv ${CI_DIR}/releases/* ${CI_DIR}

    echo "rm -rf ${CI_DIR}/releases"
    rm -rf ${CI_DIR}/releases

}

get_compiled (){

    ODB_VERSION=$1
    CI_DIR=$2
    OUTPUT_DIR="${2:-$(pwd)}"

    ODB_COMPILED_NAME="orientdb-community-${ODB_VERSION}"
    ODB_PACKAGE_EXT="tar.gz"
    ODB_COMPRESSED=${ODB_COMPILED_NAME}.${ODB_PACKAGE_EXT}

    GITHUB_URL="http://orientdb.com/download.php?email=unknown@unknown.com&os=multi&file=${ODB_COMPRESSED}"

    download ${GITHUB_URL} ${OUTPUT_DIR} ${ODB_COMPRESSED}

    ODB_PACKAGE_PATH="${CI_DIR}/${ODB_COMPRESSED}"

    extract ${ODB_PACKAGE_PATH} ${CI_DIR}

}
