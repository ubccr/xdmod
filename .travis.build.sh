#!/usr/bin/env bash

# Start a foldable section of output in the Travis log.
#
# Args:
#     $1: An identifier for the foldable section.
function start_travis_fold() {
    echo "travis_fold:start:$1"
}

# End a foldable section of output in the Travis log.
#
# Args:
#     $1: The identifier for the foldable section to end.
function end_travis_fold() {
    echo "travis_fold:end:$1"
}

# Print the results for a section of tests.
#
# Args:
#     $1: The name of the section.
#     $2: The exit code for the section.
function print_section_results() {
    section_name="$1"
    section_exit_code=$2

    if [ $section_exit_code == 0 ]; then
        echo -e "$(tput setaf 2)$section_name succeeded"'!'"$(tput sgr0)\n"
    else
        echo -e "$(tput setaf 1)$section_name failed.$(tput sgr0)\n"
    fi
}

export PATH
PATH="$(pwd)/vendor/bin:$(pwd)/node_modules/.bin:$PATH"

source ~/.nvm/nvm.sh
nvm use "$NODE_VERSION"
echo

# Fix for Travis not specifying a range if testing the first commit of
# a new branch on push
if [ -z "$TRAVIS_COMMIT_RANGE" ]; then
    TRAVIS_COMMIT_RANGE="$(git rev-parse --verify --quiet "${TRAVIS_COMMIT}^1")...${TRAVIS_COMMIT}"
fi

# Check whether the start of the commit range is available.
# If it is not available, try fetching the complete history.
commit_range_start="$(echo "$TRAVIS_COMMIT_RANGE" | sed -E 's/^([a-fA-F0-9]+).*/\1/')"
if ! git show --format='' --no-patch "$commit_range_start" &>/dev/null; then
    git fetch --unshallow

    # If it's still unavailable (likely due a push build caused by a force push),
    # tests based on what has changed cannot be run.
    if ! git show --format='' --no-patch "$commit_range_start" &>/dev/null; then
        echo "Could not find commit range start ($commit_range_start)." >&2
        echo "Tests based on changed files cannot run." >&2
        exit 1
    fi
fi

# Get the files changed by this commit (excluding deleted files).
files_changed=()
while IFS= read -r -d $'\0' file; do
    files_changed+=("$file")
done < <(git diff --name-only --diff-filter=da -z "$TRAVIS_COMMIT_RANGE")

# Separate the changed files by language.
php_files_changed=()
js_files_changed=()
json_files_changed=()
for file in "${files_changed[@]}"; do
    if [[ "$file" == *.php ]]; then
        php_files_changed+=("$file")
    elif [[ "$file" == *.js ]]; then
        js_files_changed+=("$file")
    elif [[ "$file" == *.json ]]; then
        json_files_changed+=("$file")
    fi
done

# Get any added files by language
php_files_added=()
js_files_added=()
json_files_added=()
while IFS= read -r -d $'\0' file; do
    if [[ "$file" == *.php ]]; then
        php_files_added+=("$file")
    elif [[ "$file" == *.js ]]; then
        js_files_added+=("$file")
    elif [[ "$file" == *.json ]]; then
        json_files_added+=("$file")
    fi
done < <(git diff --name-only --diff-filter=A -z "$TRAVIS_COMMIT_RANGE")

# Set up exit value for whole script and function for updating it.
script_exit_value=0

# Updates the exit value for the script as a whole.
#
# Args:
#     $1: The section exit value to consider.
function update_script_exit_value() {
    if [ $1 == 0 ]; then
        return 0
    fi
    script_exit_value=$1
}

# Perform syntax tests.
start_travis_fold syntax
echo "Running syntax tests..."

syntax_exit_value=0
for file in "${php_files_changed[@]}" "${php_files_added[@]}"; do
    php -l "$file" >/dev/null
    if [ $? != 0 ]; then
        syntax_exit_value=2
    fi
done
for file in "${json_files_changed[@]}" "${json_files_added[@]}"; do
    jsonlint --quiet --compact "$file"
    if [ $? != 0 ]; then
        syntax_exit_value=2
    fi
done

update_script_exit_value $syntax_exit_value
end_travis_fold syntax

print_section_results "Syntax tests" $syntax_exit_value

# Perform style tests.
start_travis_fold style
echo "Running style tests..."

npm install https://github.com/jpwhite4/lint-diff/tarball/master

style_exit_value=0
for file in "${php_files_changed[@]}"; do
    phpcs "$file" --report=json > "$file.lint.new.json"
    if [ $? != 0 ]; then
        git show "$commit_range_start:$file" | phpcs --stdin-path="$file" --report=json > "$file.lint.orig.json"
        ./node_modules/.bin/lint-diff "$file.lint.orig.json" "$file.lint.new.json"
        if [ $? != 0 ]; then
            style_exit_value=2
        fi
        rm "$file.lint.orig.json"
    fi
    rm "$file.lint.new.json"
done
for file in "${php_files_added[@]}"; do
    phpcs "$file"
    if [ $? != 0 ]; then
        style_exit_value=2
    fi
done
for file in "${js_files_changed[@]}"; do
    eslint "$file" -f json > "$file.lint.new.json"
    if [ $? != 0 ]; then
        git show "$commit_range_start:$file" | eslint --stdin --stdin-filename "$file" -f json > "$file.lint.orig.json"
        ./node_modules/.bin/lint-diff "$file.lint.orig.json" "$file.lint.new.json"
        if [ $? != 0 ]; then
            style_exit_value=2
        fi
        rm "$file.lint.orig.json"
    fi
    rm "$file.lint.new.json"
done
for file in "${js_files_added[@]}"; do
    eslint "$file"
    if [ $? != 0 ]; then
        style_exit_value=2
    fi
done

update_script_exit_value $style_exit_value
end_travis_fold style

print_section_results "Style tests" $style_exit_value

# Perform unit tests.
start_travis_fold unit
echo "Running unit tests..."

open_xdmod/modules/xdmod/tests/runtests.sh && phantomjs html/unit_tests/phantom.js
unit_exit_value=$?

update_script_exit_value $unit_exit_value
end_travis_fold unit

print_section_results "Unit tests" $unit_exit_value

# Perform build test.
start_travis_fold build
echo "Building Open XDMoD..."

open_xdmod/build_scripts/build_package.php --module xdmod
build_exit_value=$?

update_script_exit_value $build_exit_value
end_travis_fold build

print_section_results "Build" $build_exit_value

# If build failed, skip remaining tests.
if [ $build_exit_value != 0 ]; then
    echo "Skipping remaining tests."
    exit $script_exit_value
fi

# Perform installation test.
xdmod_install_dir="$HOME/xdmod-install"

start_travis_fold install
echo "Installing Open XDMoD..."

cd open_xdmod/build || exit 2
xdmod_tar="$(find . -regex '^\./xdmod-[0-9]+[^/]*\.tar\.gz$')"
tar -xf "$xdmod_tar"
cd "$(basename "$xdmod_tar" .tar.gz)" || exit 2
./install --prefix="$xdmod_install_dir"
install_exit_value=$?

update_script_exit_value $install_exit_value
end_travis_fold install

print_section_results "Installation" $install_exit_value

# Exit with the overall script exit code.
exit $script_exit_value
