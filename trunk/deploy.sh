#!/bin/bash

# This script can be used to upgrade/deploy an installation to a test/public server.
# By default it skipps the config.php file (copy there manually on first install)
# The script copies the application to the server and unpacks it using SSH
# It does not delete old/unused files but overwrites all deployed files!

# Mac Leopard: prevent resource fork copy
export COPYFILE_DISABLE=true

FTP=0
CFG=''

while getopts c:fdlptqmhij OPT; do
    case $OPT in
    c) CFG="-$OPTARG" ;;
    esac
done

# RESET
OPTIND=1

source deploy-config$CFG

# only exclude on HOT_SERVER
EXCLUDE_BETA_CODE=""

# standard excludes do not modify
EXCLUDE_STANDARD="--exclude=config.php --exclude=extras --exclude=deploy* --exclude=#* --exclude=.* --exclude=*.svn* --exclude=*~"

function usage
{
	cat <<-EOF
This script can be used to upgrade/deploy an installation to a test/public server.
By default it skipps the config.php file (copy there manually on first install)
The script copies the application to the server and unpacks it using SSH

`basename $0` upload the software to the server
		-t test site $TEST_SERVER:$INSTALL_PATH_TEST
		-q main site $HOT_SERVER:$INSTALL_PATH_HOT ($EXCLUDE_BETA_CODE)
		-p demo site $DEMO_SERVER:$INSTALL_PATH_DEMO ($EXCLUDE_BETA_CODE)

	OTHER:
		-c name ... use deploy-config-name instead of deploy-config
		-f use FTP instead of SSH (specifically: ncftpput - install ncftp)
		-m main site / test path $HOT_SERVER:$TEST_PATH
		-d diff local path
		-l local path $INSTALL_PATH_LOCAL
	EOF
}

DIFF=0
LOCAL=0

#Compression -j or -z
COMPR='bz2'
CMPFLAG=-j

while getopts c:fdlptqmhij OPT; do
    case $OPT in
		f) FTP=1 ;;
		d) DIFF=1 ; INSTALL_PATH=$INSTALL_PATH_LOCAL ;;
        l) LOCAL=1 ; INSTALL_PATH=$INSTALL_PATH_LOCAL ;;
		p) USER=$DEMO_USER ; SERVER=$DEMO_SERVER ; INSTALL_PATH=$INSTALL_PATH_DEMO ; EXCLUDE="$EXCLUDE_BETA_CODE $EXCLUDE" ;;
		t) SERVER=$TEST_SERVER ; INSTALL_PATH=$INSTALL_PATH_TEST ;;
		q) SERVER=$HOT_SERVER ; INSTALL_PATH=$INSTALL_PATH_HOT ; EXCLUDE="$EXCLUDE_BETA_CODE $EXCLUDE";;
		m) SERVER=$HOT_SERVER ; INSTALL_PATH=$TEST_PATH ; EXCLUDE="$EXCLUDE_BETA_CODE $EXCLUDE";;
		h) usage; exit 2;;
        *) ;;
    esac
done

if [ $DIFF == 1 ] ; then
	diff -r . $INSTALL_PATH
	exit 0;
fi

# pack
echo
echo "Packing: "
tar $EXCLUDE_STANDARD $EXCLUDE -vcjf ../WebDeployPackage.tar.$COMPR *

if [ "$INSTALL_PATH" == "" ] ; then
	echo "INSTALL_PATH empty: $SERVER, $INSTALL_PATH"
	usage;
	exit 2;
fi

if [ $LOCAL == 1 ] ; then
	echo
	echo "Deploying to: " $INSTALL_PATH
	mv ../WebDeployPackage.tar.$COMPR /tmp
	cd $INSTALL_PATH
	tar --no-same-owner $CMPFLAG -vxf /tmp/WebDeployPackage.tar.$COMPR
	rm /tmp/WebDeployPackage.tar.$COMPR
	$POST_EXEC_LOCAL
	echo
	echo "Deployed to: " $INSTALL_PATH
	exit 0;
fi

if [ "$SERVER" == "" ] ; then
	echo "SERVER path empty: $SERVER, $INSTALL_PATH"
	usage;
	exit 2;
fi

echo
echo "Deploying to: " $USER@$SERVER:$INSTALL_PATH "(Hit RETURN)"

if [ "$SERVER" == "$HOT_SERVER" ] ; then
	read input
fi

if [ $FTP == 1 ] ; then
    mkdir ../WebDeployPackage
    cd ../WebDeployPackage
    tar $CMPFLAG -xf ../WebDeployPackage.tar.$COMPR
    # copy to server
    $DRY_RUN ncftpput -u "$USER" -R "$SERVER" "$INSTALL_PATH" *
    exit 0
fi

# copy to server
$DRY_RUN scp ../WebDeployPackage.tar.$COMPR  $USER@$SERVER:~

# unpack (sourceforge needs a sleep to find the file after scp)
$DRY_RUN ssh $USER@$SERVER "cd $INSTALL_PATH; sleep 0; tar --no-same-owner $CMPFLAG --exclude=.* -vxf ~/WebDeployPackage.tar.$COMPR ; rm ~/WebDeployPackage.tar.$COMPR ; $POST_EXEC_SERVER"

echo "Deployed to: " $USER@$SERVER:$INSTALL_PATH

