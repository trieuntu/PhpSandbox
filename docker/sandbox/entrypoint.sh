#!/bin/sh
set -e

exec php -S 0.0.0.0:8080 /var/www/sandbox/index.php
