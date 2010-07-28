#!/bin/bash

PATCHNAME="TheAddressBookReloaded-2.0"

# called from trunk
cd ..

# Assume address104e = old and trunk = new
# clean junk
#find address104e -name "*.php" -print -exec dos2unix -U {} \;
#find trunk -name "*.php" -print -exec dos2unix -U {} \;
find trunk -name ".DS_Store" -print -exec rm {} \;
find trunk -name "*~" -print -exec rm {} \;

# CREATE PATCH
diff -Naur --exclude ".*" address104e trunk > $PATCHNAME.diff

# Pack
tar -vcf $PATCHNAME.tar $PATCHNAME.diff changeLog.txt HOWTOpatch.txt

# Compress
gzip --best $PATCHNAME.tar

# This needs shar-utils installed on cygwin, most Unixes should hav it ready
uuencode $PATCHNAME.tar.gz <$PATCHNAME.tar.gz >$PATCHNAME.uu

# This needs shar-utils installed on cygwin, most Unixes should hav it ready
shar -z $PATCHNAME.diff changeLog.txt HOWTOpatch.txt >$PATCHNAME.shar

