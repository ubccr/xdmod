# Format SLURM logs

This quick and dirty script was written due to an incident where old slurm files needed to be reingested.
The old slurm files were in an incompatible format from the current format so this updated those files, fixed some "issues" found along the way, and creates a file that only has the columns we actually use at this time.

**Our backup files have ALL slurm columns in case we need to go back and get more data, the files this produces should not be kept as backup files.**

Since our files are actually stored as bz2 on the server a little bash work to loop over the files uncompress them and then run the node process to have it do its thing was done.

and example of the bash script is

```bash
bunzip2 -k /scratch/historical_slurm/2017/10/dumpstats-2017-10-07.txt.bz2
node parse.js 2017-10-07
rm -rf /scratch/historical_slurm/2017/10/dumpstats-2017-10-07.txt
```

**NOTE: this was only run for our specific incident make changes and pull requests as needed**
