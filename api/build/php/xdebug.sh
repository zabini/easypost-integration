#!/bin/bash

[ $# -lt 3 ] && echo "Usage: $(basename $0) <enable> <mode> <client_host>" && exit 1

enable=$1
mode=$2
client_host=$3

if $enable; then

    ini_file="/usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini"

    pecl install xdebug
    docker-php-ext-enable xdebug

    echo "xdebug.mode = $mode" >> $ini_file
    echo "xdebug.start_with_request = yes" >> $ini_file
    echo "xdebug.client_port = 9003" >> $ini_file
    echo "xdebug.client_host = $client_host" >> $ini_file
    echo "xdebug.log = /var/www/storage/logs/xdebug/xdebug.log" >> $ini_file
    echo "xdebug.idekey = VSCODE" >> $ini_file
    echo "xdebug.cli_color = 1" >> $ini_file
    echo "xdebug.output_dir = /var/www/storage/xdebug" >> $ini_file
    echo "xdebug.profiler_output_name = cachegrind.out.%u_%p" >> $ini_file
    echo "xdebug.log_level = 7" >> $ini_file
    echo "xdebug.use_compression = false" >> $ini_file
fi
