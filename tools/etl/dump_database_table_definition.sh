#!/bin/bash

ETL_TABLE_MANAGER=etl_table_manager.php
PRETTY_PRINTER="$HOME/bin/jq '.'"
OUTPUT_DIR=.
DATABASE=
DB_HOST=
DB_USER=

if [ $# -lt 1 ]; then
    echo "Usage: $0 -d <dbname> [-c <etlconfig>] [-h <dbhost>] [-u <dbuser>] [-o <outputdir>] [-p <prettyprinter>] "
    exit 1
fi

while [[ $# > 1 ]]; do
    key="$1"

    case $key in
        -c|--config-file)
            ETL_CONFIG="-c $2"
            shift # past argument
            ;;
        -d|--database)
            DATABASE="$2"
            shift # past argument
            ;;
        -o|--output-dir)
            OUTPUT_DIR="$2"
            shift # past argument
            ;;
        -p|--pretty-printer)
            PRETTY_PRINTER="$2"
            shift # past argument
            ;;
        -h|--database-host)
            DB_HOST="-h $2"
            shift # past argument
            ;;
        -u|--database-user)
            DB_USER="-u $2"
            shift # past argument
            ;;
        *)
            # unknown option
            ;;
    esac
    shift # past argument or value
done

if [ ! -d $OUTPUT_DIR ]; then
    echo "Create output directory $OUTPUT_DIR"
    mkdir -p $OUTPUT_DIR
    if [ 0 -ne $? ]; then
        echo "Error creating output directory"
        exit 1
    fi
fi

echo "Discover table names"
CMD="mysql ${DB_HOST} ${DB_USER} -p -B -N -e 'show tables in ${DATABASE}'"
TABLE_LIST=`eval $CMD`

if [ 0 -ne $? ]; then
    echo "Error retreiving table list for ${DATABASE}"
    exit 1
fi

for table in $TABLE_LIST; do
    tablename=${DATABASE}.${table}

    outputfile=${OUTPUT_DIR}/${tablename}.json
    tmpfile=`tempfile`

    echo "Dumping $tablename to $outputfile"

    php $ETL_TABLE_MANAGER $ETL_CONFIG --discover-table $tablename --table-key table_definition \
        --output-format json --operation dump-discovered --output-file $tmpfile

    if [ 0 -ne $? ]; then
        echo "Error dumping table $tablename"
        exit 1
    fi

    if [ -n "$PRETTY_PRINTER" ]; then
        CMD="cat $tmpfile | $PRETTY_PRINTER > $outputfile"
        eval $CMD

        if [ 0 -ne $? ]; then
            echo "Error running table through pretty-printer"
            exit 1
        fi

        rm $tmpfile

    else
        mv $tmpfile $outputfile
        if [ 0 -ne $? ]; then
            echo "Error creating output file '$outputfile'"
            exit 1
        fi
    fi

done

exit 0
