#!/bin/bash

# Dynamically get the absolute path to cron.php
CRON_PHP="$(realpath "$(dirname "$0")")/cron.php"

# Add cron job: run every hour at minute 0
(crontab -l 2>/dev/null; echo "0 * * * * /usr/bin/php $CRON_PHP >/dev/null 2>&1") | crontab -