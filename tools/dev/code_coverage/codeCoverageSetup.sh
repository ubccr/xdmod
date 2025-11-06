#!/usr/bin/env bash
# This file:
#
#  - Demos BASH3 Boilerplate (change this for your script)
#
# Usage:
#
#  LOG_LEVEL=7 ./main.sh -f /tmp/x -d (change this for your script)
#
# Based on a template by BASH3 Boilerplate v2.3.0
# http://bash3boilerplate.sh/#authors
#
# The MIT License (MIT)
# Copyright (c) 2013 Kevin van Zonneveld and contributors
# You are not obligated to bundle the LICENSE file with your b3bp projects as long
# as you leave these references intact in the header comments of your source files.

# Exit on error. Append "|| true" if you expect an error.
set -o errexit
# Exit on error inside any functions or subshells.
set -o errtrace
# Do not allow use of undefined vars. Use ${VAR:-} to use an undefined VAR
#set -o nounset
# Catch the error in case mysqldump fails (but gzip succeeds) in `mysqldump |gzip`
set -o pipefail
# Turn on traces, useful while debugging but commented out by default
# set -o xtrace

if [[ "${BASH_SOURCE[0]}" != "${0}" ]]; then
  __i_am_main_script="0" # false

  if [[ "${__usage+x}" ]]; then
    if [[ "${BASH_SOURCE[1]}" = "${0}" ]]; then
      __i_am_main_script="1" # true
    fi

    __b3bp_external_usage="true"
    __b3bp_tmp_source_idx=1
  fi
else
  __i_am_main_script="1" # true
  [[ "${__usage+x}" ]] && unset -v __usage
  [[ "${__helptext+x}" ]] && unset -v __helptext
fi

# Set magic variables for current file, directory, os, etc.
__dir="$(cd "$(dirname "${BASH_SOURCE[${__b3bp_tmp_source_idx:-0}]}")" && pwd)"
__file="${__dir}/$(basename "${BASH_SOURCE[${__b3bp_tmp_source_idx:-0}]}")"
__base="$(basename "${__file}" .sh)"


# Define the environment variables (and their defaults) that this script depends on
LOG_LEVEL="${LOG_LEVEL:-6}" # 7 = debug -> 0 = emergency
NO_COLOR="${NO_COLOR:-}"    # true = disable color. otherwise autodetected


### Functions
##############################################################################

function __b3bp_log () {
  local log_level="${1}"
  shift

  # shellcheck disable=SC2034
  local color_debug="\x1b[35m"
  # shellcheck disable=SC2034
  local color_info="\x1b[32m"
  # shellcheck disable=SC2034
  local color_notice="\x1b[34m"
  # shellcheck disable=SC2034
  local color_warning="\x1b[33m"
  # shellcheck disable=SC2034
  local color_error="\x1b[31m"
  # shellcheck disable=SC2034
  local color_critical="\x1b[1;31m"
  # shellcheck disable=SC2034
  local color_alert="\x1b[1;33;41m"
  # shellcheck disable=SC2034
  local color_emergency="\x1b[1;4;5;33;41m"

  local colorvar="color_${log_level}"

  local color="${!colorvar:-${color_error}}"
  local color_reset="\x1b[0m"

  if [[ "${NO_COLOR:-}" = "true" ]] || ( [[ "${TERM:-}" != "xterm"* ]] && [[ "${TERM:-}" != "screen"* ]] ) || [[ ! -t 2 ]]; then
    if [[ "${NO_COLOR:-}" != "false" ]]; then
      # Don't use colors on pipes or non-recognized terminals
      color=""; color_reset=""
    fi
  fi

  # all remaining arguments are to be printed
  local log_line=""

  while IFS=$'\n' read -r log_line; do
    echo -e "$(date -u +"%Y-%m-%d %H:%M:%S UTC") ${color}$(printf "[%9s]" "${log_level}")${color_reset} ${log_line}" 1>&2
  done <<< "${@:-}"
}

function emergency () {                                __b3bp_log emergency "${@}"; exit 1; }
function alert ()     { [[ "${LOG_LEVEL:-0}" -ge 1 ]] && __b3bp_log alert "${@}"; true; }
function critical ()  { [[ "${LOG_LEVEL:-0}" -ge 2 ]] && __b3bp_log critical "${@}"; true; }
function error ()     { [[ "${LOG_LEVEL:-0}" -ge 3 ]] && __b3bp_log error "${@}"; true; }
function warning ()   { [[ "${LOG_LEVEL:-0}" -ge 4 ]] && __b3bp_log warning "${@}"; true; }
function notice ()    { [[ "${LOG_LEVEL:-0}" -ge 5 ]] && __b3bp_log notice "${@}"; true; }
function info ()      { [[ "${LOG_LEVEL:-0}" -ge 6 ]] && __b3bp_log info "${@}"; true; }
function debug ()     { [[ "${LOG_LEVEL:-0}" -ge 7 ]] && __b3bp_log debug "${@}"; true; }

function help () {
  echo "" 1>&2
  echo " ${*}" 1>&2
  echo "" 1>&2
  echo "  ${__usage:-No usage available}" 1>&2
  echo "" 1>&2

  if [[ "${__helptext:-}" ]]; then
    echo " ${__helptext}" 1>&2
    echo "" 1>&2
  fi

  exit 1
}


### Parse commandline options
##############################################################################

# Commandline options. This defines the usage page, and is used to parse cli
# opts & defaults from. The parsing is unforgiving so be precise in your syntax
# - A short option must be preset for every long option; but every short option
#   need not have a long option
# - `--` is respected as the separator between options and arguments
# - We do not bash-expand defaults, so setting '~/app' as a default will not resolve to ${HOME}.
#   you can use bash variables to work around this (so use ${HOME} instead)

# shellcheck disable=SC2015
[[ "${__usage+x}" ]] || read -r -d '' __usage <<-'EOF' || true # exits non-zero when EOF encountered
  -e --env            [arg] The environment variable that will determine if this script runs. If this is not supplied then the script will default to run.
  -a --value          [arg] The value that the environment variable for this script to turn.
  -b --base_dir       [arg] The directory that contains the XDMoD source code.                        Default="/root/src/github.com/ubccr/xdmod"
  -x --xdebug_script  [arg] The XDebug script that will be auto-prepended.                            Default="/root/src/github.com/ubccr/xdmod/tools/dev/start_xdebug.php"
  -p --process_script [arg] The script that is responsible for processing the raw code coverage data. Default="/root/src/github.com/ubccr/xdmod/tools/dev/combine_xdebug.php"
  -i --install_dir    [arg] Location that the XDebug script should be copied to.                      Default="/usr/share/xdmod"
  -l --profiler_dir   [arg] Location that the cachegrind files should be copied to.                   Default="/usr/share/xdmod/profiler_data"
  -c --coverage_dir   [arg] Location that the code coverage raw data will reside.                     Default="/usr/share/xdmod/coverage_data"
  -r --report_dir     [arg] Location that the code coverage reports should reside.                    Default="/usr/share/xdmod/coverage_reports"
  -v                        Enable verbose mode, print script as it is executed
  -d --debug                Enables debug mode
  -h --help                 This page
  -n --no-color             Disable color output
EOF

# shellcheck disable=SC2015
[[ "${__helptext+x}" ]] || read -r -d '' __helptext <<-'EOF' || true # exits non-zero when EOF encountered
 This script sets up XDebug code coverage for a system that has XDMoD installed.
EOF

# Translate usage string -> getopts arguments, and set $arg_<flag> defaults
while read -r __b3bp_tmp_line; do
  if [[ "${__b3bp_tmp_line}" =~ ^- ]]; then
    # fetch single character version of option string
    __b3bp_tmp_opt="${__b3bp_tmp_line%% *}"
    __b3bp_tmp_opt="${__b3bp_tmp_opt:1}"

    # fetch long version if present
    __b3bp_tmp_long_opt=""

    if [[ "${__b3bp_tmp_line}" = *"--"* ]]; then
      __b3bp_tmp_long_opt="${__b3bp_tmp_line#*--}"
      __b3bp_tmp_long_opt="${__b3bp_tmp_long_opt%% *}"
    fi

    # map opt long name to+from opt short name
    printf -v "__b3bp_tmp_opt_long2short_${__b3bp_tmp_long_opt//-/_}" '%s' "${__b3bp_tmp_opt}"
    printf -v "__b3bp_tmp_opt_short2long_${__b3bp_tmp_opt}" '%s' "${__b3bp_tmp_long_opt//-/_}"

    # check if option takes an argument
    if [[ "${__b3bp_tmp_line}" =~ \[.*\] ]]; then
      __b3bp_tmp_opt="${__b3bp_tmp_opt}:" # add : if opt has arg
      __b3bp_tmp_init=""  # it has an arg. init with ""
      printf -v "__b3bp_tmp_has_arg_${__b3bp_tmp_opt:0:1}" '%s' "1"
    elif [[ "${__b3bp_tmp_line}" =~ \{.*\} ]]; then
      __b3bp_tmp_opt="${__b3bp_tmp_opt}:" # add : if opt has arg
      __b3bp_tmp_init=""  # it has an arg. init with ""
      # remember that this option requires an argument
      printf -v "__b3bp_tmp_has_arg_${__b3bp_tmp_opt:0:1}" '%s' "2"
    else
      __b3bp_tmp_init="0" # it's a flag. init with 0
      printf -v "__b3bp_tmp_has_arg_${__b3bp_tmp_opt:0:1}" '%s' "0"
    fi
    __b3bp_tmp_opts="${__b3bp_tmp_opts:-}${__b3bp_tmp_opt}"
  fi

  [[ "${__b3bp_tmp_opt:-}" ]] || continue

  if [[ "${__b3bp_tmp_line}" =~ (^|\.\ *)Default= ]]; then
    # ignore default value if option does not have an argument
    __b3bp_tmp_varname="__b3bp_tmp_has_arg_${__b3bp_tmp_opt:0:1}"

    if [[ "${!__b3bp_tmp_varname}" != "0" ]]; then
      __b3bp_tmp_init="${__b3bp_tmp_line##*Default=}"
      __b3bp_tmp_re='^"(.*)"$'
      if [[ "${__b3bp_tmp_init}" =~ ${__b3bp_tmp_re} ]]; then
        __b3bp_tmp_init="${BASH_REMATCH[1]}"
      else
        __b3bp_tmp_re="^'(.*)'$"
        if [[ "${__b3bp_tmp_init}" =~ ${__b3bp_tmp_re} ]]; then
          __b3bp_tmp_init="${BASH_REMATCH[1]}"
        fi
      fi
    fi
  fi

  if [[ "${__b3bp_tmp_line}" =~ (^|\.\ *)Required\. ]]; then
    # remember that this option requires an argument
    printf -v "__b3bp_tmp_has_arg_${__b3bp_tmp_opt:0:1}" '%s' "2"
  fi

  printf -v "arg_${__b3bp_tmp_opt:0:1}" '%s' "${__b3bp_tmp_init}"
done <<< "${__usage:-}"

# run getopts only if options were specified in __usage
if [[ "${__b3bp_tmp_opts:-}" ]]; then
  # Allow long options like --this
  __b3bp_tmp_opts="${__b3bp_tmp_opts}-:"

  # Reset in case getopts has been used previously in the shell.
  OPTIND=1

  # start parsing command line
  set +o nounset # unexpected arguments will cause unbound variables
                 # to be dereferenced
  # Overwrite $arg_<flag> defaults with the actual CLI options
  while getopts "${__b3bp_tmp_opts}" __b3bp_tmp_opt; do
    [[ "${__b3bp_tmp_opt}" = "?" ]] && help "Invalid use of script: ${*} "

    if [[ "${__b3bp_tmp_opt}" = "-" ]]; then
      # OPTARG is long-option-name or long-option=value
      if [[ "${OPTARG}" =~ .*=.* ]]; then
        # --key=value format
        __b3bp_tmp_long_opt=${OPTARG/=*/}
        # Set opt to the short option corresponding to the long option
        __b3bp_tmp_varname="__b3bp_tmp_opt_long2short_${__b3bp_tmp_long_opt//-/_}"
        printf -v "__b3bp_tmp_opt" '%s' "${!__b3bp_tmp_varname}"
        OPTARG=${OPTARG#*=}
      else
        # --key value format
        # Map long name to short version of option
        __b3bp_tmp_varname="__b3bp_tmp_opt_long2short_${OPTARG//-/_}"
        printf -v "__b3bp_tmp_opt" '%s' "${!__b3bp_tmp_varname}"
        # Only assign OPTARG if option takes an argument
        __b3bp_tmp_varname="__b3bp_tmp_has_arg_${__b3bp_tmp_opt}"
        printf -v "OPTARG" '%s' "${@:OPTIND:${!__b3bp_tmp_varname}}"
        # shift over the argument if argument is expected
        ((OPTIND+=__b3bp_tmp_has_arg_${__b3bp_tmp_opt}))
      fi
      # we have set opt/OPTARG to the short value and the argument as OPTARG if it exists
    fi
    __b3bp_tmp_varname="arg_${__b3bp_tmp_opt:0:1}"
    __b3bp_tmp_default="${!__b3bp_tmp_varname}"

    __b3bp_tmp_value="${OPTARG}"
    if [[ -z "${OPTARG}" ]] && [[ "${__b3bp_tmp_default}" = "0" ]]; then
      __b3bp_tmp_value="1"
    fi

    printf -v "${__b3bp_tmp_varname}" '%s' "${__b3bp_tmp_value}"
    debug "cli arg ${__b3bp_tmp_varname} = (${__b3bp_tmp_default}) -> ${!__b3bp_tmp_varname}"
  done
  set -o nounset # no more unbound variable references expected

  shift $((OPTIND-1))

  if [[ "${1:-}" = "--" ]] ; then
    shift
  fi
fi


### Automatic validation of required option arguments
##############################################################################

for __b3bp_tmp_varname in ${!__b3bp_tmp_has_arg_*}; do
  # validate only options which required an argument
  [[ "${!__b3bp_tmp_varname}" = "2" ]] || continue

  __b3bp_tmp_opt_short="${__b3bp_tmp_varname##*_}"
  __b3bp_tmp_varname="arg_${__b3bp_tmp_opt_short}"
  [[ "${!__b3bp_tmp_varname}" ]] && continue

  __b3bp_tmp_varname="__b3bp_tmp_opt_short2long_${__b3bp_tmp_opt_short}"
  printf -v "__b3bp_tmp_opt_long" '%s' "${!__b3bp_tmp_varname}"
  [[ "${__b3bp_tmp_opt_long:-}" ]] && __b3bp_tmp_opt_long=" (--${__b3bp_tmp_opt_long//_/-})"

  help "Option -${__b3bp_tmp_opt_short}${__b3bp_tmp_opt_long:-} requires an argument"
done


### Cleanup Environment variables
##############################################################################

for __tmp_varname in ${!__b3bp_tmp_*}; do
  unset -v "${__tmp_varname}"
done

unset -v __tmp_varname


### Externally supplied __usage. Nothing else to do here
##############################################################################

if [[ "${__b3bp_external_usage:-}" = "true" ]]; then
  unset -v __b3bp_external_usage
  return
fi


### Signal trapping and backtracing
##############################################################################

function __b3bp_cleanup_before_exit () {
  info "Cleaning up. Done"
}
# trap __b3bp_cleanup_before_exit EXIT

# requires `set -o errtrace`
__b3bp_err_report() {
    local error_code
    error_code=${?}
    error "Error in ${__file} in function ${1} on line ${2}"
    exit ${error_code}
}
# Uncomment the following line for always providing an error backtrace
# trap '__b3bp_err_report "${FUNCNAME:-.}" ${LINENO}' ERR


### Command-line argument switches (like -d for debugmode, -h for showing helppage)
##############################################################################

# debug mode
if [[ "${arg_d:?}" = "1" ]]; then
  set -o xtrace
  LOG_LEVEL="7"
  # Enable error backtracing
  trap '__b3bp_err_report "${FUNCNAME:-.}" ${LINENO}' ERR
fi

# verbose mode
if [[ "${arg_v:?}" = "1" ]]; then
  set -o verbose
fi

# no color mode
if [[ "${arg_n:?}" = "1" ]]; then
  NO_COLOR="true"
fi

# help mode
if [[ "${arg_h:?}" = "1" ]]; then
  # Help exists with code 1
  help "Help using ${0}"
fi

[[ "${LOG_LEVEL:-}" ]] || emergency "Cannot continue without LOG_LEVEL. "

### Runtime
##############################################################################
# Check if an environment variable has been specified.
if [[ ! -z "${arg_e}" ]]; then

    # make sure that we have a value to compare the environment variable to.
    if [[ -z "${arg_a}" ]]; then
        emergency "If you specify an environment variable to check you must also supply a value."
        exit 1
    fi

    # Note: || true was added so that the script would continue, otherwise it would error out if an environment variable
    # name is supplied that doesn't exist.
    actual=$(printenv "${arg_e}" || true)

    # Ensure that the supplied environment variable actually exists / contains something.
    if [[ -z "${actual}" ]]; then
        emergency "Environment Variable ${arg_e} was not found. Exiting."
        exit 1
    fi

    # And finally, if the value supplied on the command line does not == the current value of the environment variable
    # then stop script execution. This is an expected use case and as such we exit 0.
    if [[ "${actual}" != "${arg_a}" ]]; then
        info "Expected ${arg_e} to equal ${arg_a}. But ${actual} was found. Script will now end."
        exit 0
    fi
fi

# Ensure that the base directory exists. This may be used to provide default values for the xdebug script / processing
# script values
if [[ ! -e "${arg_b}" ]]; then
    emergency "The base source directory does not exist. Unable to continue."
    exit 1
fi

# Handle the case where the base directory is specified w/ out a trailing slash.
if [[ "${arg_b: -1}" != "/" ]]; then
   arg_b="${arg_b}/"
fi

# We should have a valid base directory now,
# so make sure that we actually utilize it in the
# default value for the xdebug_script & processing script.
if [[ "${arg_x}" != *"${arg_b}"* ]]; then
    arg_x="${arg_b}tools/dev/code_coverage/start_xdebug.php"
fi

if [[ "${arg_p}" != *"${arg_b}"* ]]; then
    arg_p="${arg_b}tools/dev/code_coverage/combine_xdebug.php"
fi

########################################################################################################################
# the following if blocks are just to make sure that all the supplied / default directories that we require actually
# exist.

if [[ ! -e "${arg_x}" ]]; then
    emergency "The provided xdebug script does not exist. Unable to continue."
    exit 1
fi

if [[ ! -e "${arg_p}" ]]; then
    emergency "The provided processing script does not exist. Unable to continue"
    exit 1
fi


if [[ ! -e "${arg_i}" ]]; then
    emergency "The provided install directory does not exist. Unable to continue."
    exit 1
fi

########################################################################################################################
# *** Install / Setup XDebug ***
OS_VERSION=$(cat /etc/os-release | grep "VERSION_ID" | cut -d'=' -f 2 | tr -d '"')
XDEBUG_VERSION=3.1.6
### Install Pre-Reqs
yum -y install php-devel php-pear gcc gcc-c++ autoconf automake

### Install xdebug
pecl install Xdebug-"$XDEBUG_VERSION"

### Ensure PHP knows about xdebug and enables code coverage
echo "zend_extension=$(find /usr/lib64/php/modules/ -name xdebug.so)" > /etc/php.d/xdebug.ini
echo "xdebug.mode=coverage" >> /etc/php.d/xdebug.ini
echo "xdebug.output_dir=${arg_l}" >> /etc/php.d/xdebug.ini
## For Remote Debug uncomment the following lines...
echo "xdebug.start_with_request=yes" >> /etc/php.d/xdebug.ini
echo "xdebug.log=/var/log/xdmod/xdebug.log" >> /etc/php.d/xdebug.ini
echo "xdebug.log_level=7" >> /etc/php.d/xdebug.ini
#echo "xdebug.client_host=host.docker.internal" >> /etc/php.d/xdebug.ini
#echo "xdebug.client_port=9001" >> /etc/php.d/xdebug.ini

# Ensure xdebug log file is writable
touch /var/log/xdmod/xdebug.log
chown apache:xdmod /var/log/xdmod/xdebug.log
chmod 660 /var/log/xdmod/xdebug.log

# Ensure the various output directories are present and correct perms
for out_dir in ${arg_l} ${arg_c}; do
    mkdir -p ${out_dir}
    chown apache:xdmod ${out_dir}
    chmod 770 ${out_dir}
done

### Pre-generating the location that the xdebug script will be copied to as we'll
### be referencing it a number of times.
PREPEND_FILE_INSTALL_PATH="${arg_i}/$(basename ${arg_x})"

### Copy the auto-prepend file into place
cp "${arg_x}" "${PREPEND_FILE_INSTALL_PATH}"

### Setting php to auto-prepend our script that manages code coverage gathering
echo ';;; XDebug Hook' >> /etc/php.ini
echo "auto_prepend_file=${PREPEND_FILE_INSTALL_PATH}" >> /etc/php.ini

### Update the prepended file w/ where code coverage is supposed to be placed.
sed -i "s,__CODE_COVERAGE_DIR__,${arg_c},g" "${PREPEND_FILE_INSTALL_PATH}"

### ( Optional ) uncommenting the line below has Apache handle the auto-prepending
### as opposed to PHP. This will mean that code coverage will only be generated
### when a web request is made.
 #echo 'php_value auto_prepend_file "${PREPEND_FILE_INSTALL_PATH}"' > /etc/httpd/conf.d/codecoverage.conf

### Pre-generating the location that our code coverage processing / reporting
### script will be copied to as we'll be referencing it a number of times.
PROCESS_FILE_INSTALL_PATH="${arg_i}/$(basename ${arg_p})"

### Copy the processing script into place
cp "${arg_p}" "$PROCESS_FILE_INSTALL_PATH"

### Update the processing script w/ the required generated paths.
sed -i "s,__BASE_DIR__,${arg_b},g" "${PROCESS_FILE_INSTALL_PATH}"
echo
echo ${arg_b}
sed -i "s,__CODE_COVERAGE_DIR__,${arg_c},g" "${PROCESS_FILE_INSTALL_PATH}"
echo
echo ${arg_c}
sed -i "s,__INSTALL_DIR__,${arg_i},g" "${PROCESS_FILE_INSTALL_PATH}"
echo
echo ${arg_i}
sed -i "s,__REPORT_DIR__,${arg_r},g" "${PROCESS_FILE_INSTALL_PATH}"
echo ${arg_r}
echo
echo $PROCESS_FILE_INSTALL_PATH

~/bin/services restart
