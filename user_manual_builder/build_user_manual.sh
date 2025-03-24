#!/bin/bash
#
# build user manual from restructured text files
#


#
# Parse command line arguments
#

ARGS=$(getopt -o "b:d:xh" -l "builddir:,destdir:,xsede,help" -n "build_user_manual" -- "$@");

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
	-x|--xsede)
		MANUAL_VERSION="XSEDE"
		shift
		;;
	-h|--help)
		echo "Usage: $0" >&2
		echo "  [-b|--builddir dir] : Directory where the manual will be built [$BASE_BUILD_DIR]" >&2
		echo "  [-d|--destdir dir] : Directory that the tarball will unpack into [$DEST_DIR]" >&2
		echo "  [-x|--xsede ]: Build XSEDE version of the user manual" >&2
		echo "  [-h|--help] : Display this help" >&2
		exit 1
		;;
	--) shift ; break ;;
	esac
done

#
# Verify arguments
#

if [ -z "$BASE_BUILD_DIR" ] || [ -z "$DEST_DIR" ]; then
	echo "Must specify build directory and destination directory" >&2
	exit 1
elif [ ! -d "$BASE_BUILD_DIR" ]; then
	mkdir -p $BASE_BUILD_DIR
	if [ $? -ne 0 ]; then
		echo "Error creating base build directory: '$BASE_BUILD_DIR'" >&2
		exit 1
	fi
fi

if [ -z "$MANUAL_VERSION" ]; then
    MANUAL_VERSION="Open"
fi

#
# Format the manual for building
#

cp $BASE_BUILD_DIR/index.rst.in $BASE_BUILD_DIR/index.rst

if [ "$MANUAL_VERSION" = "XSEDE" ]; then
    sed -i "s/<XSEDE>//g" "$BASE_BUILD_DIR/index.rst"
else
    sed -i "/<XSEDE>/d" "$BASE_BUILD_DIR/index.rst"
fi

# Update copyright year
sed -i "s/copyright = '[0-9]* /copyright = '$(date +'%Y') /g" "$BASE_BUILD_DIR/conf.py"

# Update version number
XDMOD_VERSION=$(jq -r '.version' open_xdmod/modules/xdmod/build.json)
sed -i "s/release = ''/release = '$XDMOD_VERSION'/g" "$BASE_BUILD_DIR/conf.py"

#
# Build the manual
#

source $BASE_BUILD_DIR/sphinx_venv/bin/activate
sphinx-build -t $MANUAL_VERSION $BASE_BUILD_DIR $DEST_DIR

rm -rf $DEST_DIR/_sources/
