#!/bin/bash

#
# Parse command line arguments
#

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
		echo "  -v|--version manual_version : Manual version to be linked (Open or XSEDE) [$MANUAL_VERSION] \\" >&2
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

if [ -z "$BASE_BUILD_DIR" ] || [ -z "$DEST_DIR" ] || [ -z "$MANUAL_VERSION" ]; then
	echo "Must specify build_dir, dest_dir and manual_version" >&2 
	exit 1
elif [ ! -d "$BASE_BUILD_DIR" ]; then
	mkdir -p $BASE_BUILD_DIR
	if [ $? -ne 0 ]; then
		echo "Error creating base build directory: '$BASE_BUILD_DIR'" >&2
		exit 1
	fi
elif [ "$MANUAL_VERSION" != "XSEDE" ] && [ "$MANUAL_VERSION" != "Open" ]; then
	echo "Must input either Open or XSEDE for manual version"
	exit 1
fi

#
# Install dependencies
#

python3 -m venv sphinx_venv

source sphinx_venv/bin/activate

python3 -m pip install -r $BASE_BUILD_DIR/requirements.txt

#
# Build manual
#

$BASE_BUILD_DIR/build_user_manual.sh --builddir $BASE_BUILD_DIR --destdir $DEST_DIR --version $MANUAL_VERSION

#
# Cleanup venv
#

deactivate

rm -rf sphinx_venv
