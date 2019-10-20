#!/bin/bash
## 
# SCRIPT TO REBUILD THE MASTER FRAMEWORK FILE 
# 
exe=php
clear
echo '- Creating master file'
targetc=wxcore_with_comments.php
target=wxcore.php
./_clean.sh
cat wc*.php > $targetc

# THE FOLLOWING LINE WILL STRIP COMMENTS AND WHITESPACES

echo '- Stripping comments'
time $exe -q -w $targetc > $target

