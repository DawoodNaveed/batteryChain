#!/bin/bash
cd /var/www/html
scripts/initialize-system.sh
set -o errexit -o nounset -o pipefail
echo "Starting the queue worker for Battery Chain"
#/usr/bin/supervisord -c /sbin/workers/confs/battery-chain-worker.conf
