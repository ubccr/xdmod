#!/bin/bash
# needs python3, pyyaml, jq, curl, awk and sed

set -e

branches="xdmod11.0 xdmod10.5 xdmod10.0 xdmod9.5 xdmod9.0 xdmod8.5 xdmod8.1 xdmod8.0 xdmod7.5 xdmod7.0 xdmod6.6"
latest="xdmod11.0"

SED=sed
if command -v gsed > /dev/null;
then
    SED=gsed
fi

for branch in $branches;
do
    version=${branch:5}
    if ! filelist=$(git ls-tree --name-only -r upstream/$branch docs | egrep '.*\.md$'); then
        status=$?
        if [[ $status -gt 1 ]]; then
            exit $status
        fi
    fi
    for file in $filelist;
    do
        outfile=$(echo $file | awk 'BEGIN{FS="/"} { for(i=2; i < NF; i++) { printf "%s/", $i } print "'$version'/" $NF}')
        mkdir -p $(dirname $outfile)
        sedscript='/^redirect_from:$/{N;s/^redirect_from:\n    - ""/redirect_from:\n    - "\/'$version'\/"/}'
        if [ "$branch" = "$latest" ]; then
            sedscript='/^redirect_from:$/a\    - "\/'$version'\/"'
            basefile=$(basename $outfile .md)
            if [ "docs/${basefile}.md" = "$file" ]; then
                cat > ${basefile}.md << EOF
---
redirect_to: /$version/${basefile}.html
---
EOF
            fi
        fi
        git show refs/remotes/upstream/$branch:$file | $SED "$sedscript" > $outfile
    done

    if ! filelist=$(git ls-tree --name-only  upstream/$branch docs/  | egrep '.*\.(json|html)$'); then
        status=$?
        if [[ $status -gt 1 ]]; then
            exit $status
        fi
    fi
    for file in $filelist;
    do
        outfile=$(echo $file | awk 'BEGIN{FS="/"} { for(i=2; i < NF; i++) { printf "%s/", $i } print "'$version'/" $NF}')
        mkdir -p $(dirname $outfile)
        if [[ $outfile == *.html ]]; then
            if [ "$branch" = "$latest" ]; then
                basefile=$(basename $outfile .html)
                cat > ${basefile}.md << EOF
---
redirect_to: /$version/${basefile}.html
---
EOF
            fi
        fi
        git show refs/remotes/upstream/$branch:$file > $outfile
    done
done

BASEDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd $BASEDIR
python3 ./generate_security_patches.py
python3 ./get_sitemap.py
XMLLINT_INDENT='    ' xmllint --format sitemap.xml > tmp.xml && mv tmp.xml sitemap.xml
cd - > /dev/null
