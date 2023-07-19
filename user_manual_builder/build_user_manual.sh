#!/bin/bash
# -------
# build user manual from restructured text files
# -------

# Destination directory
DEST_DIR=html/user_manual

# Directory where manuals are built
BASE_BUILD_DIR=user_manual_builder

# Version of the manual (XDMoD version)
MANUAL_VERSION=Open

# ------
# Parse command line arguments
# ------

ARGS=$(getopt -o "b:d:v:h" -l "builddir:,destdir:,version:,help" -n "build_user_manual" -- "$@");

eval set -- "$ARGS";

while true; do
	case "$1" in
	-b|--builddir)
		if [ -n "$2" ]; then
			BASE_BUILD_DIR=$2
			shift 2
		fi
		;;
	-d|--destdir)
		if [ -n "$2" ]; then
			DEST_DIR=$2
			shift 2
		fi
		;;
	-v|--version)
		if [ -n "$2" ]; then
			MANUAL_VERSION=$2
			shift 2
		fi
		;;
	-h|--help)
		echo "Usage: $0 \\" >&2
		echo "  -v|--version manual_version : Manual version to be linked (open or xsede) [$MANUAL_VERSION] \\" >&2
		echo "  [-b|--builddir dir] : Directory where the manual will be built [$BASE_BUILD_DIR]" >&2
		echo "  [-d|--destdir dir] : Directory that the tarball will unpack into [$DEST_DIR]" >&2
		echo "  [-h|--help] : Display this help" >&2
		exit 1
		;;
	--) shift ; break ;;
	esac
done

#
# Verify arguments
#

if [ -z "$BASE_BUILD_DIR" -o -z "$DEST_DIR" -o -z "$MANUAL_VERSION" ]; then
	echo "Must specify build_dir, dest_dir and manual_version" >&2 
	exit 1
elif [ ! -d "$BASE_BUILD_DIR" ]; then
	mkdir -p $BASE_BUILD_DIR
	if [ $? -ne 0 ]; then
		echo "Error creating base build directory: '$BASE_BUILD_DIR'" >&2
		exit 1
	fi
fi

# ------
# Build the manual
# -----

cp $BASE_BUILD_DIR/index.rst.in $BASE_BUILD_DIR/index.rst

if [ "$MANUAL_VERSION" = "XSEDE" ]; then
    sed -i "s/<XSEDE>//g" "$BASE_BUILD_DIR/index.rst" 
else
    sed -i "/<XSEDE>/d" "$BASE_BUILD_DIR/index.rst"
fi

sphinx-build -t $MANUAL_VERSION $BASE_BUILD_DIR $DEST_DIR

rm -rf $DEST_DIR/_sources/
