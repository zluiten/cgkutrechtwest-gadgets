#! /bin/sh

PROJECT=..
BINPATH=/Applications/xampp/xamppfiles/phpdocumentor/phpdoc

IGNORE='devdocs/* support/* mugshots/* images/* languages/* styles/* lib/js/* lib/pdf/* lib/phpmailer/*'

IGN=''
for i in $IGNORE ; do
    IGN="$IGN,$TARGETPATH/$i"
done ;

php $BINPATH -t $PROJECT/devdocs -d $PROJECT --title "The Address Book Reloaded 3.3" -o HTML:frames:DOM/phphtmllib --ignore $IGN

