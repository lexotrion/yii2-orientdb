#!/usr/bin/env bash

set -e

PARENT_DIR=$(dirname $(cd "$(dirname "$0")/../.."; pwd))
#PARENT_DIR=$(dirname ${DIR}; pwd)

echo "PARENT_DIR: ${PARENT_DIR}"
