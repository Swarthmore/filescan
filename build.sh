#!/bin/bash
export NODE_ENV=production
yarn webpack
php /usr/local/var/www/moodle/admin/cli/purge_caches.php