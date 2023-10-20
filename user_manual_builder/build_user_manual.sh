#!/bin/bash
#
# build user manual from restructured text files
#


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
# Format the manual
#

cp $BASE_BUILD_DIR/index.rst.in $BASE_BUILD_DIR/index.rst

if [ "$MANUAL_VERSION" = "XSEDE" ]; then
    sed -i "s/<XSEDE>//g" "$BASE_BUILD_DIR/index.rst"
else
    sed -i "/<XSEDE>/d" "$BASE_BUILD_DIR/index.rst"
fi

# Update copyright year
sed -i "s/copyright = '/copyright = '$(date +'%Y')/g" "$BASE_BUILD_DIR/conf.py"

# Update version number
sed -i "s/release = ''/release = '$(jq -r '.version' open_xdmod/modules/xdmod/build.json)'/g" "$BASE_BUILD_DIR/conf.py"

#
# Build the manual
#

sphinx-build -E -t $MANUAL_VERSION $BASE_BUILD_DIR $DEST_DIR

rm -rf $DEST_DIR/_sources/

#
# Testing
#

# Input HTML file
input_file="$DEST_DIR/index.html"

# Check if Compliance is properly removed/built
grep -q "Compliance Tab" "$input_file"
if [ $? -eq 0 ]
then
    if [ "$MANUAL_VERSION" = "Open" ]; then
        echo "Error removing Compliance Tab from table of contents"
        exit 1
    fi
else
    if [ "$MANUAL_VERSION" = "XSEDE" ]; then
        echo "Error building Compliance Tab"
        exit 1
    fi
fi

