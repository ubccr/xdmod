import moment, { tz } from 'moment-timezone';
import { createInterface } from 'readline';
import { createReadStream, createWriteStream } from 'fs';
const outputStreams = {};
const timezone = 'America/New_York';
const offsets = ['-0400', '-0500'];
const outFormat = "YYYY-MM-DDTHH:mm:ss";
const zone = tz.zone(timezone);

const date = process.argv[2].split('-');
const year = date[0];
const month = date[1];
const day = date[2] || '';

// Our files are stored in two different ways but are all within the same path
// with a year
// let slurmLogPath = '/scratch/historical_slurm';

let slurmLogPath = 'Put your base path here';

let outPath = '/scratch/slurm';

// We started to do daily logs at some point, if a day is specified change the input path
if (day === '') {
    inputFile = `${slurmLogPath}/${year}/dumpstats-${year}-${month}.txt`;
    outPath = `${outPath}/${year}/`;
}
else {
    inputFile = `${slurmLogPath}/${year}/${month}/dumpstats-${year}-${month}-${day}.txt`;
    outPath = `${outPath}/${year}/${month}/`;
}

const rl = createInterface({
    input: createReadStream(inputFile)
});

const parseSlurmDuration = (durationString) => {
    const keys = ['days', 'hours', 'minutes', 'seconds'];
    const match = /^(?:(?:(\d+)-)?(\d+):)?(\d+):(\d+)(?:\.\d+)?$/.exec(durationString);
    let durationParts = {};
    for (let i = 0; i < keys.length; i++) {
        if ('string' === typeof keys[i]) {
            durationParts[keys[i]] = parseInt(match[i + 1]) || 0;
        }
    }
    return durationParts.days * 86400
        + durationParts.hours * 3600
        + durationParts.minutes * 60
        + durationParts.seconds
}

const hasAmbiguousTime = (m) => {
    const t = [60, -60, 30, -30];
    const a = t.map(
        (x) => {
            return moment(m).add(x, 'm').format('HH:mm');
        });
    return a.indexOf(m.format('HH:mm')) > -1;
};

const timefix = (start, end, duration /* in seconds */) => {

    let munged;

    let mstart = tz(start, timezone);
    if (!mstart.isValid()) {
        munged = 'invalid start';
    }

    let mend = tz(end, timezone);
    if (!mend.isValid()) {
        munged = 'invalid end';
    }

    if (hasAmbiguousTime(mstart) && hasAmbiguousTime(mend)) {
        var possible_starts = [];
        var possible_ends = [];
        for (let i = 0; i < offsets.length; i++) {
            possible_starts.push(moment(start + offsets[i]).utc());
            possible_ends.push(moment(end + offsets[i]).utc());
        }

        var interval;
        for (let i = 0; i < possible_starts.length; i++) {
            for (let j = 0; j < possible_ends.length; j++) {
                interval = possible_ends[j].diff(possible_starts[i]);
                if (interval == duration * 1000) {
                    return [possible_starts[i].format(outFormat), possible_ends[j].format(outFormat), 'both'];
                }
            }
        }
        console.log('Time is crazy!');
    }

    if (hasAmbiguousTime(mstart)) {
        munged = 'start';
        mend = mend.utc();
        mstart = moment(mend).subtract(duration, 'seconds');
    }
    else if (hasAmbiguousTime(mend)) {
        munged = 'end';
        mstart = mstart.utc();
        mend = moment(mstart).add(duration, 'seconds');
    }
    else {
        mstart.utc();
        mend.utc();
    }
    const retval = [mstart.format(outFormat), mend.format(outFormat), munged];
    delete mstart;
    delete mend;
    return retval;
}
// https://stackoverflow.com/questions/14480345/how-to-get-the-nth-occurrence-in-a-string
// https://stackoverflow.com/a/14482123
const nthIndex = (str, pat, n) => {
    const len = str.length;
    let i = -1;
    while (n-- && i++ < len) {
        i = str.indexOf(pat, i);
        if (i < 0) {
            break;
        }
    }
    return i;
}

let lineNum = 0;
let expectedColumns = 0;
let count = 0;
const requiredColumns = [
    'jobid', 'jobidraw', 'cluster', 'partition', 'account', 'group', 'gid',
    'user', 'uid', 'submit', 'eligible', 'start', 'end', 'elapsed', 'exitcode', 'state', 'nnodes', 'ncpus',
    'reqcpus', 'reqmem', 'reqgres', 'reqtres', 'timelimit', 'nodelist', 'jobname'
];
const usedStates = ['CANCELLED', 'COMPLETED', 'FAILED', 'NODE_FAIL', 'PREEMPTED', 'TIMEOUT'];
let readOrder = [];

rl.on('line', function (line) {
    let data = [];
    if (lineNum === 0) {
        lineNum++;
        data = line.toString().toLowerCase().split('|');
        expectedColumns = data.length;
        requiredColumns.forEach((column) => {
            const index = data.indexOf(column);
            if (index === -1) {
                console.log(`Missing ${column}`);
                process.exit();
            }
            readOrder.push(index);
        });
    }
    else {
        lineNum++;
        data = line.toString().split('|');
        const columnDiff = data.length - expectedColumns;
        if (columnDiff) {
            console.info(`${inputFile} :+${lineNum} jobid: ${data[readOrder[0]]} | in jobName`);
            const beginJobName = nthIndex(line, '|', readOrder[24]);
            let left = line.substring(0, beginJobName).split('|');
            let right = line.substring(beginJobName + 1).split('|');
            let jobName = [];
            for (let i = 0; i <= columnDiff; i++) {
                jobName.push(right.shift());
            }
            data = left.concat(jobName.join('|')).concat(right);
            if (data.length !== expectedColumns) {
                console.error('Broke Something');
                process.exit();
            }
        }
        else if (columnDiff < 0) {
            console.error(data.length, '!=', expectedColumns, inputFile, lineNum);
            console.log(line);
            console.log(data);
            process.exit();
        }
        const newData = [];
        const newDataObj = {};
        if (
            data[readOrder[0]].indexOf('.') === -1
            &&
            (
                data[readOrder[15]].startsWith('CANCELLED') || usedStates.indexOf(data[readOrder[15]]) > -1
            )
        ) {
            let i = 0;
            readOrder.forEach((index) => {
                let thisData = data[index];
                newDataObj[requiredColumns[i]] = thisData;
                newData.push(thisData)
                i++;
            });
            /*
             * Need to make sure submit and eligible are correct as well.
             */
            const fixedStartEnd = timefix(newData[11], newData[12], parseSlurmDuration(newData[13]));
            if (fixedStartEnd[2]) {
                console.warn(`munged ${fixedStartEnd[2]} time(s) line: ${lineNum} jobid: ${newData[0]}`);
                /*
                console.log(newDataObj);
                newDataObj.submit = moment.tz(newData[9], timezone).utc().format("YYYY-MM-DDTHH:mm:ss");
                newDataObj.eligible = moment.tz(newData[10], timezone).utc().format("YYYY-MM-DDTHH:mm:ss");
                newDataObj.start = fixedStartEnd[0];
                newDataObj.end = fixedStartEnd[1];
                console.log('after:');
                console.log(newDataObj);
                */
                //count++;
            }
            if (newData[9] != 'Unknown') {
                submitTime = tz(newData[9], timezone);
                if (submitTime.isValid()) {
                    newData[9] = submitTime.utc().format("YYYY-MM-DDTHH:mm:ss");
                }
                else {
                    //console.log('invalid submit Time:',newData[9], newData[1] );
                }
            }

            if (newData[10] != 'Unknown') {
                elTime = tz(newData[10], timezone);
                if (elTime.isValid()) {
                    newData[10] = elTime.utc().format("YYYY-MM-DDTHH:mm:ss");
                }
                else {
                    //console.log('invalid el time:',newData[10], newData[1] );
                }
            }

            newData[11] = fixedStartEnd[0];
            newData[12] = fixedStartEnd[1];
            const cluster = newData[2];
            if (!outputStreams[cluster]) {
                let outFile = `${outPath}${cluster}-${year}-${month}`;
                if (day !== '') {
                    outFile = `${outFile}-${day}.log`
                }
                else {
                    outFile = `${outFile}.log`
                }
                outputStreams[cluster] = createWriteStream(outFile);
            }
            //console.log(newData.join('|'));
            outputStreams[cluster].write(newData.join('|') + '\n');
            newdata = [];
        }
    }
});

rl.on('end', () => {
    Object.keys(outputStreams).forEach((v) => { v.end(); })
});
