#!/bin/bash

# UNUSED sample script:
#
# shrink images automatically to 128x150 and 
# put them into the medium subdirectory
# this scales mugshots automatically (e.g. from a cron script)
# this script needs to be intagrated somehow with TAB

rm medium/*.jpg 

for i in *.jpg ; do
echo $i
	# we resize first to double the size 256x300 and then shrink
	convert -resize 256x -resize "x300<" -resize 50% -gravity center -crop 128x150+0+0 +repage -quality 95 $i medium/$i
done


