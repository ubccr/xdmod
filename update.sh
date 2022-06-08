#!/bin/bash
# needs python3, pyyaml, jq, curl, awk and sed

set -e

branches="xdmod10.0 xdmod9.5"
latest="xdmod10.0"

for branch in $branches;
do
    version=${branch:5}
    filelist=$(git ls-tree --name-only -r upstream/$branch docs | egrep '.*\.md$')
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
        git show refs/remotes/upstream/$branch:$file | sed "$sedscript" > $outfile
    done

    filelist=$(git ls-tree --name-only  upstream/$branch docs/  | egrep '.*\.(json|html)$')
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
python3 ./get_sitemap.py
cd -
