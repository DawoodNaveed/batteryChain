#!/bin/bash
scripts/initialize-system.sh
set -o errexit -o nounset -o pipefail
cd /var/www/html
bin/console app:create-transaction-hash
