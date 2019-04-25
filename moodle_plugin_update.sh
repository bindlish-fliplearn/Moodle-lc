#!/bin/bash

domain=(`grep "case" config.php | cut -d "'" -f2`)
len=${#domain[@]}
for (( i=0; i<$len; i++ ))
do
 sudo -u www-data php admin/cli/primeupgrade.php ${domain[$i]} --non-interactive --allow-unstable

done
