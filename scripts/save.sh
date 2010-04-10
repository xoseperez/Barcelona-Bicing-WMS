#!/bin/bash
file=$0
path=${file%/*}
cd $path
/usr/local/php/bin/php ./save.php

