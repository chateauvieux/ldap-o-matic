#!/bin/sh

orig=/home/cchateau/MIG_metamerge_specific
mig_dir=/www/test/MIG

rm -f $mig_dir/php/config.php
ln -s $orig/config.php $mig_dir/php/config.php

rm -f $mig_dir/welcome.html
ln -s $orig/welcome.html $mig_dir/welcome.html

rm -f $mig_dir/images/logo.gif
ln -s $orig/logo.gif $mig_dir/images/logo.gif

rm -f $mig_dir/top.html
ln -s $orig/top.html $mig_dir/top.html
