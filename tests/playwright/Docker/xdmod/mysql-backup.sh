#!/bin/bash

# **********************************************************************************************************************
# ** THIS FILE IS MANAGED BY PUPPET, MODIFY AT YOUR OWN RISK! **
# **********************************************************************************************************************

MYSQL_DUMP=$(which mysqldump)
HOSTNAME="$(hostname)"
DUMP_DIR=${1:-/root}
HOST_DUMP_DIR="${DUMP_DIR}/${HOSTNAME}"
LOG_DIR="/var/log"
LOG="${LOG_DIR}/mysql-backup.log"

# Regex for excluding some databases from backup
EXCLUDE=${2:-schema|mysql|test}

CREDENTIALS_FILE="/root/.my.cnf"

# List of all MySQL ports to back up. This allows us to back up multiple servers on the same
# host such as multiple replication slaves.
MYSQL_PORT_LIST=( 3306 )

MAIL_FAILURE_SUBJECT=""
MYSQL_MAIL_FAILURE_TO=""
DEFAULT_MAIL_FAILURE_TO=""
MAIL_FAILURE_TO="${MYSQL_MAIL_FAILURE_TO:-$DEFAULT_MAIL_FAILURE_TO}"

# Log messages to a file with a timestamp
function log {
    echo -e "[$(date "+%Y-%m-%d %H:%M:%S")] $1" >>"${LOG}"
}

echo >>"${LOG}"
log "Starting MySQL backup"

# Make sure that the log file is readable by all
chmod 0644 "${LOG}"

# Create the dump directory if it doesn't already exist
if [[ ! -d "${HOST_DUMP_DIR}" ]]; then
    if ! mkdir -v -p "${HOST_DUMP_DIR}" &>"${LOG}"; then
        log "Could not create dump directory '${HOST_DUMP_DIR}'"
        exit 1
    fi
fi

for port in "${MYSQL_PORT_LIST[@]}"; do
    log "Backing up mysql server on port ${port}"

    # Ensure that MySQL daemon is running
    if mysqladmin --defaults-file="${CREDENTIALS_FILE}" -P "${port}" status \
        | grep -qiv "Uptime" &>>"${LOG}"; then
        log "ERROR mysql not running on port ${port}"
        continue
    fi

    # Retrieve list of MySQL databases to backup
    DB_LIST="$(mysql --defaults-file="${CREDENTIALS_FILE}" -P "${port}" -N -e "SHOW DATABASES" \
        | grep -E -v "${EXCLUDE}" \
        | tr "\n" " ")"
    if [[ -z "${DB_LIST// }" ]]; then
        log "ERROR Could not retrieve database list from port ${port}"
        continue
    fi

    # Perform the backups
    for db in ${DB_LIST}; do
        FILE="${HOST_DUMP_DIR}/${db}-dump-${port}.sql.gz"
        log "Backing up '${db}' on port ${port} to ${FILE}"
        "${MYSQL_DUMP}" --defaults-file="${CREDENTIALS_FILE}" -P "${port}" --routines --triggers --opt --databases "${db}" \
            | gzip 2>&1 1>"${FILE}" \
            | tee -a "${LOG}"
        if [[ 0 -ne "${PIPESTATUS[0]}" ]]; then
            log "ERROR Could not dump database '${db}' on port ${port}"
            tail -n 18 "${LOG}" | mail -s "${MAIL_FAILURE_SUBJECT}" "${MAIL_FAILURE_TO}"
        else
            chmod 0640 "${FILE}" 2>>"${LOG}"
            ls -lh "${FILE}" >>"${LOG}"
        fi
    done
done

log "Finished MySQL backup"
