#!/bin/bash

function usage
{
	cat <<-EOF
	`basename $0` will precompile js and css files into single files.
    
	These options are recognized:		Default:

	-a   precompile all
	-d   delete all
	-j   JS only
	-c   CSS only
	EOF
} 

while getopts cjad OPT; do
    case $OPT in
        d) rm -f lib/js/all.pre.js; find styles -name '*.pre.css' -print -exec rm {} \; ; ;;
        c) cd styles; php -f csscompile.php ; cd .. ;;
        j) cd lib/js; ./precompile.sh; cd ../.. ;;
        a) cd lib/js; ./precompile.sh; cd ../.. ; cd styles; php -f csscompile.php ; cd .. ;;
        *) echo unrecognized option: $OPT; usage; exit 2;;
    esac
done

