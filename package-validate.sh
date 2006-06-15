#!/bin/sh

for f in package2*.xml; do 
    printf "Checking $f... "
    if pear package-validate $f > /dev/null; then
        echo OK
    else
        echo FAILED
        echo ---------------------------
        pear package-validate $f 
        echo ---------------------------
    fi
done    
